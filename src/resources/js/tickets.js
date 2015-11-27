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
		var $event_pickers = $( '#tribe-event-datepickers' ),
			$tribe_tickets = $( '#tribetickets' ),
			$tickets_container = $( '#event_tickets' ),
			$body = $( 'html, body' ),
			startofweek = 0;

		$tribe_tickets.on( {
			/**
			 * Makes a Visual Spining thingy appear on the Tickets metabox.
			 * Also prevents user Action on the metabox elements.
			 *
			 * @param  {jQuery.event} event  The jQuery event
			 * @param  {string} action You can use `start` or `stop`
			 * @return {void}
			 */
			'spin.tribe': function( event, action ) {
				if ( typeof action === 'undefined' || $.inArray( action, [ 'start', 'stop' ] ) ){
					action = 'stop';
				}

				if ( 'stop' === action ) {
					$tickets_container.css( 'opacity', '1' )
						.find( '#tribe-loading' ).hide();
				} else {
					$tickets_container.css( 'opacity', '0.5' )
						.find( '#tribe-loading' ).show();

				}
			},

			/**
			 * Clears the Form fields the correct way
			 *
			 * @return {void}
			 */
			'clear.tribe': function() {
				var $this = $( this ),
					$ticket_form = $this.find( '#ticket_form' );

				$this.find( 'a#ticket_form_toggle' ).show();

				$this.find( 'input:not(:button):not(:radio):not(:checkbox):not([type="hidden"]), textarea' ).val( '' );
				$this.find( 'input:checkbox' ).attr( 'checked', false );
				$this.find( '#ticket_id' ).val( '' );

				// some fields may have a default value we don't want to lose after clearing the form
				$this.find( 'input[data-default-value]' ).each( function() {
					var $current_field = $( this );
					$current_field.val( $current_field.data( 'default-value' ) );
				} );

				// Reset the min/max datepicker settings so that they aren't inherited by the next ticket that is edited
				$this.find( '#ticket_start_date' ).datepicker( 'option', 'maxDate', null );
				$this.find( '#ticket_end_date' ).datepicker( 'option', 'minDate', null );

				$this.find( '.ticket_start_time, .ticket_end_time, .ticket.sale_price' ).hide();

				$this.find( '#ticket_price' ).removeProp( 'disabled' )
					.siblings( '.no-update-message' ).html( '' ).hide()
					.end().siblings( '.description' ).show();

				$ticket_form.hide();
			},

			/**
			 * Scrolls to the Tickets container, to show it when required
			 *
			 * @return {void}
			 */
			'focus.tribe': function() {
				$body.animate( {
					scrollTop: $tickets_container.offset().top - 50
				}, 500 );
			},

			/**
			 * Sets/Swaps out the name & id attributes on Advanced ticket meta fields so we don't have (or submit)
			 * duplicate fields
			 *
			 * @return {void}
			 */
			'set-advanced-fields.tribe': function() {
				var $this = $( this );
				var $ticket_form = $this.find( '#ticket_form' );
				var $ticket_advanced = $ticket_form.find( 'tr.ticket_advanced' ).find( 'input, select, textarea' );
				var provider = $ticket_form.find( '#ticket_provider:checked' ).val();

				// for each advanded ticket input, select, and textarea, relocate the name and id fields a bit
				$ticket_advanced.each( function() {
					var $el = $( this );

					// if there's a value in the name attribute, move it to the data attribute then clear out the id as well
					if ( $el.attr( 'name' ) ) {
						$el.data( 'name', $el.attr( 'name' ) ).attr( {
							'name': '',
							'id': ''
						} );
					}

					// if the field is for the currently selected provider, make sure the name and id fields are populated
					if (
						$el.closest( 'tr' ).hasClass( 'ticket_advanced_' + provider )
						&& $el.data( 'name' )
						&& 0 === $el.attr( 'name' ).length
					) {
						$el.attr( {
							'name': $el.data( 'name' ),
							'id': $el.data( 'name' )
						} );
					}
				} );
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
			$tribe_tickets.trigger( 'set-advanced-fields.tribe' );
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
			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'set-advanced-fields.tribe' )
				.trigger( 'focus.tribe' );
			$( '#ticket_form' ).show();
			e.preventDefault();
		} );

		/* "Cancel" button action */
		$( '#ticket_form_cancel' ).click( function() {
			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'set-advanced-fields.tribe' )
				.trigger( 'focus.tribe' );
		} );

		/* "Save Ticket" button action */
		$( '#ticket_form_save' ).click( function( e ) {
			var $form = $( '#ticket_form_table' ),
				type = $form.find( '#ticket_provider:checked' ).val(),
				$rows = $form.find( '.ticket, .ticket_advanced_' + type );

			$tribe_tickets.trigger( 'save-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

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
					$tribe_tickets.trigger( 'saved-ticket.tribe', response );

					if ( response.success ) {
						$tribe_tickets.trigger( 'clear.tribe' );
						$( 'td.ticket_list_container' ).empty().html( response.data );
						$( '.ticket_time' ).hide();
					}
				},
				'json'
			).complete( function() {
				$tribe_tickets.trigger( 'spin.tribe', 'stop' ).trigger( 'focus.tribe' );
			} );

		} );

		/* "Delete Ticket" link action */

		$tribe_tickets.on( 'click', '.ticket_delete', function( e ) {

			e.preventDefault();

			$tribe_tickets.trigger( 'delete-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

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
					$tribe_tickets.trigger( 'deleted-ticket.tribe', response );

					if ( response.success ) {
						$tribe_tickets.trigger( 'clear.tribe' );
						$( 'td.ticket_list_container' ).empty().html( response.data );
					}
				},
				'json'
			).complete( function() {
				$tribe_tickets.trigger( 'spin.tribe', 'stop' );
			} );
		} );

		/* "Edit Ticket" link action */

		$tribe_tickets
			.on( 'click', '.ticket_edit', function( e ) {

				e.preventDefault();

				$( 'h4.ticket_form_title_edit' ).show();
				$( 'h4.ticket_form_title_add' ).hide();

				$tribe_tickets.trigger( 'spin.tribe', 'start' );

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
						$tribe_tickets
							.trigger( 'clear.tribe' )
							.trigger( 'set-advanced-fields.tribe' )
							.trigger( 'edit-ticket.tribe', response );

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

						var $ticket_advanced = $( 'tr.ticket_advanced input' );
						$ticket_advanced.data( 'name', $ticket_advanced.attr( 'name' ) ).attr( {
							'name': '',
							'id': ''
						} );
						$( 'tr.ticket_advanced_' + response.data.provider_class ).remove();
						$( 'tr.ticket.bottom' ).before( response.data.advanced_fields );

						// set the prices after the advanced fields have been added to the form
						var $ticket_price = $tribe_tickets.find( '#ticket_price' );
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

						var $sale_field = $tribe_tickets.find( '#ticket_sale_price' );

						if ( onSale ) {
							$sale_field
								.val( salePrice )
								.closest( 'tr' )
								.show();
						} else {
							$sale_field.closest( 'tr' ).hide();
						}

						if ( 'undefined' !== typeof response.data.purchase_limit && response.data.purchase_limit ) {
							$( '#ticket_purchase_limit' ).val( response.data.purchase_limit );
						}

						$( 'input:radio[name=ticket_provider]' ).filter( '[value=' + response.data.provider_class + ']' ).click();

						$tribe_tickets.find( '.bumpdown-trigger' ).bumpdown();
						$tribe_tickets.find( '.bumpdown' ).hide();

						$( 'a#ticket_form_toggle' ).hide();
						$( '#ticket_form' ).show();

					},
					'json'
				).complete( function() {
					$tribe_tickets.trigger( 'spin.tribe', 'stop' ).trigger( 'focus.tribe' );
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

		if ( $( '#tribe_ticket_header_preview img' ).length ) {

			var $tiximg = $( '#tribe_ticket_header_preview img' );
			$tiximg.removeAttr( 'width' ).removeAttr( 'height' );

			if ( $tribe_tickets.width() < $tiximg.width() ) {
				$tiximg.css( 'width', '95%' );
			}
		}
	} );

})( window, jQuery );
