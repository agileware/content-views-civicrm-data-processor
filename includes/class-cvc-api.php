<?php

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

			} catch ( CiviCRM_API3_Exception $e ) {

				return WP_Error( 'CVC CiviCRM Api error', $e->getMessage(), $params );

			}

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

			return $this->call( $entity, $action, $params )['values'];

		}

		/**
		 * Shorthand for getting data processor info
		 * @param $id
		 *
		 * @return mixed
		 */
		public function get_data_processor_by_id( $id ) {
			if ( empty( $id ) ) {
				throw new Exception( 'No id passed in.' );
			}
			$dp     = $this->call_values( 'DataProcessorOutput', 'get', [
				'sequential'        => 1,
				'type'              => "api",
				'data_processor_id' => $id
			] );
			return array_shift( $dp );
		}

	}

}

