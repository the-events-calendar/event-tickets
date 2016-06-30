jQuery( document ).ready( function( $ ) {

	if ( typeof AttendeesPointer !== 'undefined' && null !== AttendeesPointer ) {
		options = $.extend( AttendeesPointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: AttendeesPointer.pointer_id,
					action : 'dismiss-wp-pointer'
				} );
			},
			open: function( event, widget ) {
				widget.pointer
					.css({
						top: parseInt( widget.pointer.css( 'top' ).replace( 'px', '' ), 10 ) + 5
					})
					.find( '.wp-pointer-arrow' ).css({
						right: '50px',
						left: 'auto'
					} );

				widget.element.on({
					'click': function() {
						widget.element.pointer( 'close' );
					}
				});
			}
		} );

		var $pointer = $( AttendeesPointer.target ).pointer( options ).pointer( 'open' ).pointer( 'widget' );
	}

	$( 'input.print' ).on( 'click', function( e ) {
		window.print();
	} );

	var $filter_attendee = $( document.getElementById( 'filter_attendee' ) );

	$filter_attendee.on( 'keydown', function( e ) {
		// if enter was pressed, pretend it wasn't
		if ( 13 === e.keyCode ) {
			return false;
		}
	} );

	$filter_attendee.on( 'keyup paste', function() {

		var search = jQuery( this ).val().toLowerCase();

		$( '#the-list' ).find( 'tr' ).each( function( i, e ) {

			var row = $( e );

			// Search by code (order, attendee and security numbers)
			var order = row.children( 'td.order_id' ).children( 'a' ).text();
			var attendee = row.children( 'td.attendee_id' ).text();
			var security = row.children( 'td.security' ).text();
			var code_found = attendee.indexOf( search ) === 0 || order.indexOf( search ) === 0 || security.indexOf( search ) === 0;

			// Search by name (we will also look at second/third names etc, not just the first name)
			var name = row.children( 'td.purchaser_name').text().toLowerCase();
			var name_found = name.indexOf( search ) === 0 || name.indexOf( " " + search ) > 1;

			if ( code_found || name_found ) {
				row.show();
			}
			else {
				row.hide();
			}
		} );

	} );

	$( '.tribe-attendees-email' ).on({
		'submit': function( event ) {
			$( '.tribe-attendees-email' ).hide();
			$( document.getElementById( 'tribe-loading' ) ).show();
		}
	});


	$( '.tickets_checkin' ).click( function( e ) {

		var obj = jQuery( this );

		var params = {
			action  : 'tribe-ticket-checkin-' + obj.attr( 'data-provider' ),
			provider: obj.attr( 'data-provider' ),
			order_ID: obj.attr( 'data-attendee-id' ),
			nonce   : Attendees.checkin_nonce
		};
		
		// add event_ID information if available
		if ( obj.attr( 'data-event-id' ) ) {
			params.event_ID = obj.attr( 'data-event-id' );
		}

		$.post(
			ajaxurl,
			params,
			function( response ) {
				if ( response.success ) {
					obj.parent( 'td' ).parent( 'tr' ).addClass( 'tickets_checked' );

					$( '#total_checkedin' ).text( parseInt( $( '#total_checkedin' ).text() ) + 1 );
				}
			},
			'json'
		);

		e.preventDefault();
	} );


	$( '.tickets_uncheckin' ).click( function( e ) {

		var obj = jQuery( this );

		var params = {
			action  : 'tribe-ticket-uncheckin-' + obj.attr( 'data-provider' ),
			provider: obj.attr( 'data-provider' ),
			order_ID: obj.attr( 'data-attendee-id' ),
			nonce   : Attendees.uncheckin_nonce
		};

		// add event_ID information if available
		if ( obj.attr( 'data-event-id' ) ) {
			params.event_ID = obj.attr( 'data-event-id' );
		}

		$.post(
			ajaxurl,
			params,
			function( response ) {
				if ( response.success ) {
					obj.parent( 'span' ).parent( 'td' ).parent( 'tr' ).removeClass( 'tickets_checked' );
					$( '#total_checkedin' ).text( parseInt( $( '#total_checkedin' ).text() ) - 1 );
				}
			},
			'json'
		);

		e.preventDefault();
	} );

	function tribe_is_email( emailAddress ) {
		var pattern = new RegExp( /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i );
		return pattern.test( emailAddress );
	}

	function tribe_validate_email() {
		$( '#email_errors' ).removeClass( 'ui-state-error' ).addClass( 'ui-state-highlight' ).text( Attendees.sending );
		var $address = $( '#email_to_address' ).val();
		var $user = $( '#email_to_user' ).val();
		var $email = false;

		if ( $user > - 1 ) {
			$email = $user;
		}

		if ( $.trim( $address ) !== '' && tribe_is_email( $address ) ) {
			$email = $address;
		}

		if ( ! $email ) {
			$( '#email_errors' ).removeClass( 'ui-state-highlight' ).addClass( 'ui-state-error' ).text( Attendees.required );
		}

		return $email;
	}

	function tribe_array_filter( arr ) {

		var retObj = {},
			k;

		for ( k in arr ) {
			if ( arr[k] ) {
				retObj[k] = arr[k];
			}
		}

		return retObj;
	}


} );
