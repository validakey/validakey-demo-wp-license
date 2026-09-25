(function( $ ) {
	'use strict';

	function syncTokenTypeFields() {
		var tokenType = $( '#validakey_demo_token_type' ).val() || '1';
		// 2 Limited: duration and/or uses. 3 Subscription time. 4 Subscription use.
		var showDuration = ( tokenType === '2' || tokenType === '3' );
		var showUses = ( tokenType === '2' || tokenType === '4' );
		var showRecurrence = ( tokenType === '3' || tokenType === '4' );
		var isLimited = ( tokenType === '2' );

		$( '.validakey-demo-field-duration' ).toggle( showDuration );
		$( '.validakey-demo-field-uses' ).toggle( showUses );
		$( '.validakey-demo-field-recurrence' ).toggle( showRecurrence );
		$( '.validakey-demo-duration-hint-limited, .validakey-demo-uses-hint-limited' ).toggle( isLimited );
		$( '.validakey-demo-field-cost, .validakey-demo-field-tax, .validakey-demo-field-total' ).show();

		if ( tokenType === '3' && String( $( '#validakey_demo_duration' ).val() || '' ).trim() === '' ) {
			$( '#validakey_demo_duration' ).val( '3600' );
		}
		if ( tokenType === '4' && String( $( '#validakey_demo_uses' ).val() || '' ).trim() === '' ) {
			$( '#validakey_demo_uses' ).val( '1' );
		}

		syncTotal();
	}

	function parseMoney( raw ) {
		var value = parseFloat( String( raw || '' ).trim() );
		return isFinite( value ) ? value : 0;
	}

	function syncTotal() {
		var cost = parseMoney( $( '#validakey_demo_cost_usd' ).val() );
		var tax = parseMoney( $( '#validakey_demo_tax_usd' ).val() );
		var total = cost + tax;
		var hasInput = String( $( '#validakey_demo_cost_usd' ).val() || '' ).trim() !== ''
			|| String( $( '#validakey_demo_tax_usd' ).val() || '' ).trim() !== '';

		$( '#validakey_demo_total_usd' ).val( hasInput ? total.toFixed( 2 ) : '' );
	}

	$( function() {
		if ( ! $( '.validakey-demo-create-license-form' ).length ) {
			return;
		}

		$( '#validakey_demo_token_type' ).on( 'change', syncTokenTypeFields );
		$( '#validakey_demo_cost_usd, #validakey_demo_tax_usd' ).on( 'input change', syncTotal );
		syncTokenTypeFields();
	} );

})( jQuery );
