<?php

/**
 * Content_Views_CiviCRM_Query class.
 */
class Content_Views_CiviCRM_Query {

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var Content_Views_CiviCRM Reference to plugin instance
	 */
	protected $cvc;

	/**
	 * Constructor.
	 *
	 * @param object $cvc Reference to plugin instance
	 */
	public function __construct( $cvc ) {
		$this->cvc = $cvc;
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 * @since 0.1
	 */
	public function register_hooks() {
		// alter query params
		add_filter( PT_CV_PREFIX_ . 'query_parameters', [ $this, 'alter_query_parameters' ] );
		// filter query params
		add_filter( PT_CV_PREFIX_ . 'query_params', [ $this, 'filter_query_params' ] );
	}

	/**
	 * Alter query parameters before filtering them.
	 *
	 * @param array $args WP_Query parameters
	 *
	 * @return array $args WP_Query parameters
	 * @since 0.1
	 */
	public function alter_query_parameters( $args ) {
		if ( $args['post_type'] != 'civicrm' ) {
			return $args;
		}
		$id                = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'data_processor_id', true ) );
		$sort              = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'civicrm_sort', true ) );
		$limit             = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'civicrm_limit', true ) );
		$pagination_enable = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'enable-pagination', true ) );
		$pagination_limit  = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'pagination-items-per-page', true ) );
		$params            = [
			'options' => [],
		];
		if ( $sort ) {
			$params['options']['sort'] = $sort;
		}
		if ( $limit || $limit === '0' ) {
			$params['options']['limit'] = $limit;
		}
		// pagination
		if ( $pagination_enable == 'yes' && ! empty( $pagination_limit ) ) {
			$params['options']['limit'] = $pagination_limit;
		}

		if ( isset( $args['offset'] ) ) {
			$params['options']['offset'] = max( 0, (int) $args['offset'] );
		}

		// Only the exposed filters of this data processor may be set from the
		// request. Anything else (API options, return fields, chained calls,
		// check_permissions, ...) must never reach the API call.
		$id     = ( is_scalar( $id ) && ctype_digit( (string) $id ) ) ? (int) $id : 0;
		$fields = [];
		if ( $id > 0 ) {
			$fields = $this->cvc->api->call_values( 'DataProcessorFilter', 'get', [
				'is_exposed'        => 1,
				'data_processor_id' => $id,
				'options'           => [ 'limit' => 0 ],
			] );
		}

		// live filters (exposed, not required)
		$live_filters = $this->get_live_filter_values();
		foreach ( $fields as $field ) {
			if ( empty( $field['name'] ) || ! empty( $field['is_required'] ) ) {
				continue;
			}
			if ( isset( $live_filters[ $field['name'] ] ) ) {
				$this->set_filter_param( $params, $field['name'], $live_filters[ $field['name'] ] );
			}
		}

		// hidden filters
		foreach ( $fields as $field ) {
			if ( empty( $field['name'] ) ) {
				continue;
			}
			if ( $field['is_required'] && $this->cvc->dp_options->has_option( $field['name'], Content_Views_CiviCRM_Dp_Option::USER_CONTACT_ID ) ) {
				// The current contact filter must never be taken from the request,
				// otherwise any visitor could read another contact's records.
				$contact_id = (int) CRM_Core_Session::getLoggedInContactID();
				if ( $contact_id <= 0 ) {
					// Not logged in: a missing filter value would return every row.
					$args['civicrm_no_results'] = true;
				}
				$params[ $field['name'] ] = $contact_id;
				continue;
			}
			// fixme all required filters should be an exception

			if ( isset( $_REQUEST[ $field['name'] ] ) && is_scalar( $_REQUEST[ $field['name'] ] ) ) {
				$requestValue = \CRM_Utils_Request::retrieveValue( $field['name'], 'String' );
				if ( ! empty( $requestValue ) && is_scalar( $requestValue ) ) {
					$params[ $field['name'] ] = (string) $requestValue;
				}
			}
		}

		$args['civicrm_api_params'] = $params;
		$args['data_processor_id']  = $id;

		return $args;
	}

	/**
	 * Read the live filter values posted by Content Views Pro.
	 *
	 * Only scalar values are kept, nested arrays from the request are dropped.
	 *
	 * @return array filter name => string value
	 */
	protected function get_live_filter_values() {
		if ( empty( $_POST['query'] ) || ! is_string( $_POST['query'] ) ) {
			return [];
		}
		parse_str( wp_unslash( $_POST['query'] ), $query );
		$values = [];
		foreach ( $query as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$values[ (string) $key ] = (string) $value;
			}
		}

		return $values;
	}

	/**
	 * Add a filter value from the request to the API parameters.
	 *
	 * @param array $params API parameters
	 * @param string $name filter name (already checked against the data processor filters)
	 * @param string $value scalar value from the request
	 */
	protected function set_filter_param( array &$params, $name, $value ) {
		if ( $value === '' ) {
			return;
		}
		if ( $this->cvc->dp_options->has_option( $name, Content_Views_CiviCRM_Dp_Option::CONTACT_NAME_SEARCH ) ) {
			$ids = $this->search_contact_name( $value );
			// no match must return no rows, never every row
			$params[ $name ] = [ 'IN' => $ids ? $ids : [ 0 ] ];
		} else {
			$params[ $name ] = $value;
		}
	}

	protected function search_contact_name( string $search ) {
		$result = $this->cvc->api->call_values( 'Contact', 'get', [
			'sequential' => 1,
			'return'     => [ "id" ],
			'sort_name'  => $search,
			'options'    => [ 'limit' => 0 ]
		] );
		$ids    = [];
		foreach ( $result as $item ) {
			$ids[] = $item['id'];
		}

		return $ids;
	}

	/**
	 * Filters query parameters before they WP_Query is instantiated.
	 *
	 * @param array $args WP_Query parameters
	 *
	 * @return array $args WP_Query parameters
	 * @since 0.1
	 */
	public function filter_query_params( $args ) {
		if ( $args['post_type'] == 'civicrm' ) // bypass query
		{
			$this->bypass_query( $args );
		}

		return $args;
	}


	/**
	 * Bypasses the WP_Query.
	 *
	 * When quering a Contact post type bypasses the WP_Query
	 * and use Civi's API to retrieve Contacts.
	 *
	 * @param array $args Query args to instantiate WP_Query
	 *
	 * @uses 'posts_pre_query'
	 * @since 0.1
	 */
	public function bypass_query( $args ) {
		// bypass query
		add_filter( 'posts_pre_query', function ( $posts, $class ) use ( $args ) {
			if ( isset( $class->query['post_type'] ) && ( $class->query['post_type'] !== 'civicrm' ) ) {
				return $posts;
			}
			if ( empty( $args['data_processor_id'] ) || ! empty( $args['civicrm_no_results'] ) ) {
				return [];
			}
			$dp = $this->cvc->api->get_data_processor_by_id( $args['data_processor_id'] );
			if ( empty( $dp ) ) {
				return [];
			}
			$result = $this->cvc->api->call_values( $dp['api_entity'], $dp['api_action'], $args['civicrm_api_params'] );

			// clear posts from previous short codes
			$posts = [];

			// mock WP_Posts contacts
			foreach ( $result as $item ) {
				$post                    = new WP_Post( (object) [] );
				$post->ID                = $item['id'];
				$post->post_type         = 'civicrm';
				$post->filter            = 'raw'; // set to raw to bypass sanitization
				$post->data_processor_id = $args['data_processor_id'];

				// title - replace the placeholder with its value in current item
				$title = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'civicrm_title', true ) );
				if ( ! empty( $title ) ) {
					$title = preg_replace_callback( '/\${(.*)}/U',
						function ( $matches ) use ( $item ) {
							return !empty( $item[ $matches[1] ] ) ? $item[ $matches[1] ] : '' ;
						},
						$title );
				}
				$post->post_title = $title;

				// clean object
				foreach ( $post as $prop => $value ) {
					if ( ! in_array( $prop, [ 'ID', 'post_title', 'post_type', 'filter', 'data_processor_id' ] ) ) {
						unset( $post->$prop );
					}
				}
				// add rest of contact properties
				foreach ( $item as $field => $value ) {
					if ( ! in_array( $field, [ 'hash' ] ) ) {
						$post->$field = $value;
					}
				}

				// build array
				$posts[] = $post;

			}

			return $posts;

		}, 10, 2 );
	}
}
