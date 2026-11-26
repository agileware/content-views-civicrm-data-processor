/**
 * Content Views CiviCRM: hide the Content Views "Common" and "Advance"
 * filter groups on the view edit screen when the content type is CiviCRM.
 * Those settings do not apply to CiviCRM views.
 */
( function ( $ ) {
	'use strict';

	var config = window.contentViewsCiviCRMFilterSettings || {};
	var labels = config.labels || [];
	var field  = '[name="pt-cv-content-type"]';

	function contentType() {
		var $field = $( field );
		if ( ! $field.length ) {
			return '';
		}

		// A radio list, or a dropdown when there are many content types.
		return ( $field.is( ':radio' ) ? $field.filter( ':checked' ).val() : $field.val() ) || '';
	}

	// Top-level groups of the Filter Settings tab with a matching label.
	function groups() {
		return $( '#pt-cv-filter-settings .pt-form-group' ).filter( function () {
			var $group = $( this );
			if ( $group.parents( '.pt-form-group' ).length ) {
				return false;
			}

			return $.inArray( $.trim( $group.children( 'label.control-label' ).text() ), labels ) !== -1;
		} );
	}

	function update() {
		groups().toggle( contentType() !== 'civicrm' );
	}

	$( function () {
		update();
		$( document ).on( 'change', field, update );
	} );
} )( jQuery );
