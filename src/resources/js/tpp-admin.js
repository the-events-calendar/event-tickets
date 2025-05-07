const tribe_tickets_tpp_admin = {
	l10n: window.tribe_tickets_tpp_admin_strings || false,
};

( function ( $, my, strings ) {
	'use strict';

	my.checkmarkValidationMap = function () {
		return {
			email( email ) {
				const re =
					/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				return re.test( email );
			},
			radio( val ) {
				return [ 'yes', '1', 1, true, 'true', 'on', 'complete', 'completed' ].includes( val.toLowerCase() );
			},
		};
	};

	my.castStatusToBool = function ( status ) {
		const radio = my.checkmarkValidationMap().radio;
		return radio( status );
	};

	my.castBoolToStatus = function ( val ) {
		return val === true ? 'complete' : 'incomplete';
	};

	my.updatePayPalIpnStatus = function () {
		const $find = $( '#paypal-ipn-config-status' );
		const $dependsOn = $( '.ipn-required' );

		if ( ! $dependsOn ) {
			return;
		}

		const newStatus = _.reduce(
			$dependsOn,
			function ( currentStatusBool, el ) {
				return currentStatusBool && ! $( el ).hasClass( 'no-checkmark' );
			},
			true
		);
		const newStatusSlug = my.castBoolToStatus( newStatus );
		$find.text( my.l10n[ newStatusSlug ] ).attr( 'data-status', newStatusSlug );
	};

	my.isOkInput = function ( input ) {
		const $parent = $( input ).closest( '.checkmark' );

		if ( ! $parent ) {
			return;
		}

		let ok = false;
		const map = my.checkmarkValidationMap();

		if ( $parent.hasClass( 'tribe-field-email' ) ) {
			ok = map.email( input.value );
		} else if ( $parent.hasClass( 'tribe-field-radio' ) ) {
			const value = $( input ).closest( '.tribe-field-wrap' ).find( 'input:checked' ).val();
			ok = map.radio( value );
		} else {
			ok = true;
		}

		return ok;
	};

	my.toggleCheckmark = function () {
		const ok = my.isOkInput( this );
		const $parent = $( this ).closest( '.checkmark' );

		if ( ok ) {
			$parent.removeClass( 'no-checkmark' );
		} else {
			$parent.addClass( 'no-checkmark' );
		}

		my.updatePayPalIpnStatus();
	};

	my.setupValidationOnPanel = function ( event, data ) {
		if ( ! ( data.panel && data.panel instanceof jQuery ) ) {
			return;
		}

		const $panel = data.panel;

		const paypalIsDefaultProvider = $panel.data( 'default-provider' ) === 'Tribe__Tickets__Commerce__PayPal__Main';
		const isNew = ! $( '#ticket_id' ).val();

		if ( paypalIsDefaultProvider && isNew ) {
			$( '#ticket_price, #ticket_sale_price' )
				.prop( 'data-required', true )
				.attr( 'data-validation-is-greater-than', '0' );
		}

		$panel.find( '.tribe-validation' ).validation();
	};

	my.init = function () {
		$( '.checkmark input' ).each( function () {
			$( this ).on( 'change', my.toggleCheckmark ).each( my.toggleCheckmark );
		} );

		$( '#event_tickets' ).on( 'after_panel_swap.tickets', my.setupValidationOnPanel );
	};

	$( function () {
		if ( ! my.l10n ) {
			return;
		}
		my.init();
	} );
} )( jQuery, tribe_tickets_tpp_admin, tribe_tickets_tpp_admin_strings );
