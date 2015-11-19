var ticketHeaderImage = window.ticketHeaderImage || {};

(function( window, $, undefined ) {
	'use strict';

	ticketHeaderImage = {

		// Call this from the upload button to initiate the upload frame.
		uploader: function() {

			var frame = wp.media( {
				title   : HeaderImageData.title,
				multiple: false,
				library : { type: 'image' },
				button  : { text: HeaderImageData.button }
			} );

			// Handle results from media manager.
			frame.on( 'close', function() {
				var attachments = frame.state().get( 'selection' ).toJSON();
				if ( attachments.length ) {
					ticketHeaderImage.render( attachments[0] );
				}
			} );

			frame.open();
			return false;
		},
		// Output Image preview and populate widget form.
		render  : function( attachment ) {
			$( '#tribe_ticket_header_preview' ).html( ticketHeaderImage.imgHTML( attachment ) );
			$( '#tribe_ticket_header_image_id' ).val( attachment.id );
			$( '#tribe_ticket_header_remove' ).show();
		},
		// Render html for the image.
		imgHTML : function( attachment ) {
			var img_html = '<img src="' + attachment.url + '" ';
			img_html += 'width="' + attachment.width + '" ';
			img_html += 'height="' + attachment.height + '" ';
			img_html += '/>';
			return img_html;
		}
	};


	$( document ).ready( function() {
		var $event_pickers = $( '#tribe-event-datepickers' );

		var startofweek = 0,
			$tribeTickets = $( '#tribetickets' ),
			$ticketsContainer = $( '#event_tickets' ),
			$body = $( 'html, body' );

		$tribeTickets.on( {
			/**
			 * Makes a Visual Spining thingy appear on the Tickets metabox.
			 * Also prevents user Action on the metabox elements.
			 *
			 * @param  {jQuery.event} event  The jQuery event
			 * @param  {string} action You can use `start` or `stop`
			 * @return {void}
			 */
			'spin.tribe': function( event, action ) {
				if ( typeof	action === 'undefined' || $.inArray( action, [ 'start', 'stop' ] ) ){
					action = 'stop';
				}

				if ( 'stop' === action ) {
					$ticketsContainer.css( 'opacity', '1' )
						.find( '#tribe-loading' ).hide();
				} else {
					$ticketsContainer.css( 'opacity', '0.5' )
						.find( '#tribe-loading' ).show();

				}
			},

			/**
			 * Clears the Form fields the correct way
			 *
			 * @param  {jQuery.event} event  The jQuery event
			 * @return {void}
			 */
			'clear.tribe': function( event ) {
				var $this = $( this );

				$this.find( 'a#ticket_form_toggle' ).show();

				$this.find( '#ticket_form input:not(:button):not(:radio):not(:checkbox)' ).val( '' );
				$this.find( '#ticket_form input:checkbox' ).attr( 'checked', false );

				// Reset the min/max datepicker settings so that they aren't inherited by the next ticket that is edited
				$this.find( '#ticket_start_date' ).datepicker( 'option', 'maxDate', null );
				$this.find( '#ticket_end_date' ).datepicker( 'option', 'minDate', null );

				$this.find( '.ticket_start_time' ).hide();
				$this.find( '.ticket_end_time' ).hide();
				$this.find( '.ticket.sale_price' ).hide();

				var $ticket_price = $this.find( document.getElementById( 'ticket_price' ) );
				var $no_update_message = $ticket_price.siblings( '.no-update-message' );

				$no_update_message.html( '' ).hide();
				$ticket_price.removeProp( 'disabled' );
				$ticket_price.siblings( '.description' ).show();

				$this.find( '#ticket_form textarea' ).val( '' );
				$this.find( '#ticket_form' ).hide();
			},

			/**
			 * Scrolls to the Tickets container, to show it when required
			 *
			 * @param  {jQuery.event} event  The jQuery event
			 * @return {void}
			 */

			'focus.tribe': function( event ) {
				$body.animate( {
					scrollTop: $ticketsContainer.offset().top - 50
				}, 500 );
			}

		} );

		if ( $event_pickers.length ) {
			startofweek = $event_pickers.data( 'startofweek' );
		}

		var datepickerOpts = {
			dateFormat     : 'yy-mm-dd',
			showAnim       : 'fadeIn',
			changeMonth    : true,
			changeYear     : true,
			numberOfMonths : 3,
			firstDay       : startofweek,
			showButtonPanel: true,
			onChange       : function() {
			},
			onSelect       : function( dateText, inst ) {
				var the_date = $.datepicker.parseDate( 'yy-mm-dd', dateText );
				if ( inst.id === 'ticket_start_date' ) {
					$( '#ticket_end_date' ).datepicker( 'option', 'minDate', the_date );
					if ( the_date ) {
						$( '.ticket_start_time' ).show();
					}
					else {
						$( '.ticket_start_time' ).hide();
					}
				}
				else {
					$( '#ticket_start_date' ).datepicker( 'option', 'maxDate', the_date );
					if ( the_date ) {
						$( '.ticket_end_time' ).show();
					}
					else {
						$( '.ticket_end_time' ).hide();
					}
				}
			}
		};

		$( '#ticket_start_date' ).datepicker( datepickerOpts ).keyup( function( e ) {
			if ( e.keyCode === 8 || e.keyCode === 46 ) {
				$.datepicker._clearDate( this );
			}
		} );
		$( '#ticket_end_date' ).datepicker( datepickerOpts ).keyup( function( e ) {
			if ( e.keyCode === 8 || e.keyCode === 46 ) {
				$.datepicker._clearDate( this );
			}
		} );

		/* Show the advanced metabox for the selected provider and hide the others on selection change */
		$( 'input[name=ticket_provider]:radio' ).change( function() {
			$( 'tr.ticket_advanced' ).hide();
			$( 'tr.ticket_advanced_' + this.value + ':not(.sale_price)' ).show();
		} );

		/* Show the advanced metabox for the selected provider and hide the others at ready */
		$( 'input[name=ticket_provider]:checked' ).each( function() {
			var $ticket_advanced = $( 'tr.ticket_advanced' );
			$ticket_advanced.hide()
				.filter( 'tr.ticket_advanced_' + this.value )
				.not( '.sale_price' )
				.show();
		} );

		/* "Add a ticket" link action */
		$( 'a#ticket_form_toggle' ).click( function( e ) {
			$( 'h4.ticket_form_title_edit' ).hide();
			$( 'h4.ticket_form_title_add' ).show();
			$( this ).hide();
			$tribeTickets.trigger( 'clear.tribe' ).trigger( 'focus.tribe' );
			$( '#ticket_form' ).show();
			e.preventDefault();
		} );

		/* "Cancel" button action */
		$( '#ticket_form_cancel' ).click( function() {
			$tribeTickets.trigger( 'clear.tribe' ).trigger( 'focus.tribe' );
		} );

		/* "Save Ticket" button action */
		$( '#ticket_form_save' ).click( function( e ) {
			var $form = $( '#ticket_form_table' ),
				type = $form.find( '#ticket_provider:checked' ).val(),
				$rows = $form.find( '.ticket, .ticket_advanced_' + type );

			$tribeTickets.trigger( 'save-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

			var params = {
				action  : 'tribe-ticket-add-' + $( 'input[name=ticket_provider]:checked' ).val(),
				formdata: $rows.find( '.ticket_field' ).serialize(),
				post_ID : $( '#post_ID' ).val(),
				nonce   : TribeTickets.add_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					$tribeTickets.trigger( 'saved-ticket.tribe', response );

					if ( response.success ) {
						$tribeTickets.trigger( 'clear.tribe' );
						$( 'td.ticket_list_container' ).empty().html( response.data );
						$( '.ticket_time' ).hide();
					}
				},
				'json'
			).complete( function() {
				$tribeTickets.trigger( 'spin.tribe', 'stop' ).trigger( 'focus.tribe' );
			} );

		} );

		/* "Delete Ticket" link action */

		$tribeTickets.on( 'click', '.ticket_delete', function( e ) {

			e.preventDefault();

			$tribeTickets.trigger( 'delete-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

			var params = {
				action   : 'tribe-ticket-delete-' + $( this ).attr( 'attr-provider' ),
				post_ID  : $( '#post_ID' ).val(),
				ticket_id: $( this ).attr( 'attr-ticket-id' ),
				nonce    : TribeTickets.remove_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					$tribeTickets.trigger( 'deleted-ticket.tribe', response );

					if ( response.success ) {
						$tribeTickets.trigger( 'clear.tribe' );
						$( 'td.ticket_list_container' ).empty().html( response.data );
					}
				},
				'json'
			).complete( function() {
				$tribeTickets.trigger( 'spin.tribe', 'stop' );
			} );
		} );

		/* "Edit Ticket" link action */

		$tribeTickets
			.on( 'click', '.ticket_edit', function( e ) {

				e.preventDefault();

				$( 'h4.ticket_form_title_edit' ).show();
				$( 'h4.ticket_form_title_add' ).hide();

				$tribeTickets.trigger( 'spin.tribe', 'start' );

				var params = {
					action   : 'tribe-ticket-edit-' + $( this ).attr( 'attr-provider' ),
					post_ID  : $( '#post_ID' ).val(),
					ticket_id: $( this ).attr( 'attr-ticket-id' ),
					nonce    : TribeTickets.edit_ticket_nonce
				};

				$.post(
					ajaxurl,
					params,
					function( response ) {
						$tribeTickets;

						$tribeTickets.trigger( 'clear.tribe' ).trigger( 'edit-ticket.tribe', response );

						var regularPrice = response.data.price;
						var salePrice    = regularPrice;
						var onSale       = false;

						if ( 'undefined' !== typeof response.data.on_sale && response.data.on_sale ) {
							onSale       = true;
							regularPrice = response.data.regular_price;
						}

						$( '#ticket_id' ).val( response.data.ID );
						$( '#ticket_name' ).val( response.data.name );
						$( '#ticket_description' ).val( response.data.description );

						if ( onSale ) {
							$( '.ticket_advanced_' + response.data.provider_class + '.sale_price' ).show();
						}

						var start_date = response.data.start_date.substring( 0, 10 );
						var end_date = response.data.end_date.substring( 0, 10 );

						$( '#ticket_start_date' ).val( start_date );
						$( '#ticket_end_date' ).val( end_date );

						var $start_meridian = $( document.getElementById( 'ticket_start_meridian' ) ),
						      $end_meridian = $( document.getElementById( 'ticket_end_meridian' ) );

						if ( response.data.start_date ) {
							var start_hour = parseInt( response.data.start_date.substring( 11, 13 ) );
							var start_meridian = 'am';

							if ( start_hour > 12 && $start_meridian.length ) {
								start_meridian = 'pm';
								start_hour = parseInt( start_hour ) - 12;
								start_hour = ( '0' + start_hour ).slice( - 2 );
							}
							if ( 12 === start_hour ) {
								start_meridian = 'pm';
							}
							if ( 0 === start_hour && 'am' === start_meridian ) {
								start_hour = 12;
							}

							// Return the start hour to a 0-padded string
							start_hour = start_hour.toString();
							if ( 1 === start_hour.length ) {
								start_hour = '0' + start_hour;
							}

							$( '#ticket_start_hour' ).val( start_hour );
							$( '#ticket_start_meridian' ).val( start_meridian );

							$( '.ticket_start_time' ).show();
						}

						if ( response.data.end_date ) {

							var end_hour = parseInt( response.data.end_date.substring( 11, 13 ) );
							var end_meridian = 'am';

							if ( end_hour > 12 && $end_meridian.length ) {
								end_meridian = 'pm';
								end_hour = parseInt( end_hour ) - 12;
								end_hour = ( '0' + end_hour ).slice( - 2 );
							}
							if ( end_hour === 12 ) {
								end_meridian = 'pm';
							}
							if ( 0 === end_hour && 'am' === end_meridian ) {
								end_hour = 12;
							}

							// Return the end hour to a 0-padded string
							end_hour = end_hour.toString();
							if ( 1 === end_hour.length ) {
								end_hour = '0' + end_hour;
							}

							$( '#ticket_end_hour' ).val( end_hour );
							$( '#ticket_end_meridian' ).val( end_meridian );

							$( '#ticket_start_minute' ).val( response.data.start_date.substring( 14, 16 ) );
							$( '#ticket_end_minute' ).val( response.data.end_date.substring( 14, 16 ) );

							$( '.ticket_end_time' ).show();
						}

						$( 'tr.ticket_advanced_' + response.data.provider_class ).remove();
						$( 'tr.ticket.bottom' ).before( response.data.advanced_fields );

						// set the prices after the advanced fields have been added to the form
						var $ticket_price = $( document.getElementById( 'ticket_price' ) );
						$ticket_price.val( regularPrice );

						if ( 'undefined' !== typeof response.data.disallow_update_price_message ) {
							$ticket_price.siblings( '.no-update-message' ).html( response.data.disallow_update_price_message );
						} else {
							$ticket_price.siblings( '.no-update-message' ).html( '' );
						}

						if ( 'undefined' !== typeof response.data.can_update_price && ! response.data.can_update_price ) {
							$ticket_price.prop( 'disabled', 'disabled' );
							$ticket_price.siblings( '.description' ).hide();
							$ticket_price.siblings( '.no-update-message' ).show();
						} else {
							$ticket_price.removeProp( 'disabled' );
							$ticket_price.siblings( '.description' ).show();
							$ticket_price.siblings( '.no-update-message' ).hide();
						}

						$( '#ticket_sale_price' ).val( salePrice );

						$( 'input:radio[name=ticket_provider]' ).filter( '[value=' + response.data.provider_class + ']' ).click();

						$( 'a#ticket_form_toggle' ).hide();
						$( '#ticket_form' ).show();

					},
					'json'
				).complete( function() {
					$tribeTickets.trigger( 'spin.tribe', 'stop' ).trigger( 'focus.tribe' );
				} );

			} )
			.on( 'click', '#tribe_ticket_header_image', function( e ) {
				e.preventDefault();
				ticketHeaderImage.uploader( '', '' );
			} );


		var $remove = $( '#tribe_ticket_header_remove' );
		var $preview = $( '#tribe_ticket_header_preview' );

		if ( $preview.find( 'img' ).length ) {
			$remove.show();
		}

		$('body').on( 'click', '#tribe_ticket_header_remove', function( e ) {

			e.preventDefault();
			$preview.html( '' );
			$remove.hide();
			$( '#tribe_ticket_header_image_id' ).val( '' );

		} );

		/* Helper functions */

		function tribe_fix_image_width() {
			if ( $tribeTickets.width() < $tiximg.width() ) {
				$tiximg.css( 'width', '95%' );
			}
		}

		if ( $( '#tribe_ticket_header_preview img' ).length ) {

			var $tiximg = $( '#tribe_ticket_header_preview img' );
			$tiximg.removeAttr( 'width' ).removeAttr( 'height' );

			tribe_fix_image_width();
		}
	} );

})( window, jQuery );
