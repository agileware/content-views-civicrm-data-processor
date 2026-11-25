<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Views CiviCRM Api class.
 *
 * @since 0.1
 */

if ( ! class_exists( 'Content_Views_CiviCRM_Api' ) ) {

	class Content_Views_CiviCRM_Api {

		/**
		 * Call CiviCRM API.
		 *
		 * @param string $entity
		 * @param string $action
		 * @param array $params
		 *
		 * @return array $result
		 * @since  0.1
		 */
		public function call( $entity, $action, $params ) {

			try {

				return civicrm_api3( $entity, $action, $params );

			} catch ( Exception $e ) {

				// Never show API error details to the visitor, they can reveal
				// entity names, SQL and configuration. Log them instead.
				if ( class_exists( 'Civi' ) ) {
					Civi::log()->error( 'Content Views CiviCRM: API call failed: {entity}.{action}: {message}', [
						'entity'  => is_string( $entity ) ? $entity : '',
						'action'  => is_string( $action ) ? $action : '',
						'message' => $e->getMessage(),
					] );
				}

			}

			return [
				'is_error' => 1,
				'count'    => 0,
				'values'   => [],
			];
		}

		/**
		 * Get CiviCRM API values.
		 *
		 * @param string $entity
		 * @param string $action
		 * @param array $params
		 *
		 * @return array $result
		 * @since  0.1
		 */
		public function call_values( $entity, $action, $params ) {

			$result = $this->call( $entity, $action, $params );

			return ( is_array( $result ) && isset( $result['values'] ) && is_array( $result['values'] ) ) ? $result['values'] : [];

		}

		/**
		 * Shorthand for getting data processor info
		 *
		 * @param int $id
		 *
		 * @return array|null The API output of the data processor, or null when
		 *   the id is invalid or has no usable API output.
		 */
		public function get_data_processor_by_id( $id ) {
			if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id <= 0 ) {
				return null;
			}
			$dp = $this->call_values( 'DataProcessorOutput', 'get', [
				'sequential'        => 1,
				'type'              => "api",
				'data_processor_id' => (int) $id
			] );

			$dp = array_shift( $dp );
			if ( empty( $dp['api_entity'] ) || ! is_string( $dp['api_entity'] )
			     || empty( $dp['api_action'] ) || ! is_string( $dp['api_action'] ) ) {
				return null;
			}

			return $dp;
		}

		/**
		 * Whether calls to a data processor API output check the permission
		 * configured on that output for the current user.
		 *
		 * Calls made from PHP skip CiviCRM permission checks by default, which
		 * would let anonymous visitors read any data processor output. Sites
		 * that deliberately expose a restricted output can turn this off with
		 * the 'content_views_civicrm_check_permissions' filter.
		 *
		 * @param array $dp the data processor API output
		 *
		 * @return bool
		 */
		public function check_dp_permissions( $dp ) {
			return (bool) apply_filters( 'content_views_civicrm_check_permissions', true, $dp );
		}

		public function is_dp_enabled() {
			static $entities = null;
			if ( ! $entities ) {
				$entities = $this->call_values( 'Entity', 'get', [] );
			}

			return in_array( 'DataProcessor', $entities );
		}

	}

}

