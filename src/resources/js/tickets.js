var ticketHeaderImage = window.ticketHeaderImage || {};

(function( window, $, undefined ) {
	'use strict';

	var $event_pickers               = $( document.getElementById( 'tribe-event-datepickers' ) );
	var $tribe_tickets               = $( document.getElementById( 'tribetickets' ) );
	var $tickets_container           = $( document.getElementById( 'event_tickets' ) );
	var $enable_global_stock         = $( document.getElementById( 'tribe-tickets-enable-global-stock' ) );
	var $global_stock_level          = $( document.getElementById( 'tribe-tickets-global-stock-level' ) );
	var global_stock_setting_changed = false;
	var $body                        = $( 'html, body' );
	var startofweek                  = 0;
	var $panels                      = $( document.getElementById( 'event_tickets' ) ).find( '.ticket_panel' );
	var $base_panel                  = $( document.getElementById( 'tribe_panel_base' ) );
	var $edit_panel                  = $( document.getElementById( 'tribe_panel_edit' ) );
	var $settings_panel              = $( document.getElementById( 'tribe_panel_settings' ) );

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
			$( document.getElementById( 'tribe_ticket_header_preview' ) ).html( ticketHeaderImage.imgHTML( attachment ) );
			$( document.getElementById( 'tribe_ticket_header_image_id' ) ).val( attachment.id );
			$( document.getElementById( 'tribe_ticket_header_remove' ) ).show();
			$( document.getElementById( 'tribe_tickets_image_preview_filename' ) ).find( '.filename' ).text( attachment.filename );
			$( document.getElementById( '#tribe_tickets_image_preview_filename' ) ).show();
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
		$tribe_tickets.on( {
			/**
			 * Makes a Visual Spinning thingy appear on the Tickets metabox.
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
				var $ticket_panel = $( document.getElementById( 'tribe_panel_edit' ) );

				$ticket_panel.find( 'input:not(:button):not(:radio):not(:checkbox):not([type="hidden"]), textarea' ).val( '' );
				$ticket_panel.find( 'input:checkbox, input:radio' ).prop( 'checked', false );
				$ticket_panel.find( '#ticket_id' ).val( '' );

				// some fields may have a default value we don't want to lose after clearing the form
				$ticket_panel.find( 'input[data-default-value]' ).each( function() {
					var $current_field = $( this );
					$current_field.val( $current_field.data( 'default-value' ) );
				} );

				// Reset the min/max datepicker settings so that they aren't inherited by the next ticket that is edited
				$ticket_panel.find( '#ticket_start_date' ).datepicker( 'option', 'maxDate', null );
				$ticket_panel.find( '#ticket_end_date' ).datepicker( 'option', 'minDate', null );

				$ticket_panel.find( '#ticket_price' ).removeProp( 'disabled' )
					.siblings( '.no-update-message' ).html( '' ).hide()
					.end().siblings( '.description' ).show();

				$( document.getElementById( 'tribe-tickets-attendee-sortables' ) ).empty();

				$( '.accordion-content.is-active' ).removeClass( 'is-active' );

				$( document.getElementById( 'ticket_bottom_right' ) ).empty();

				$ticket_panel.find( '.accordion-header, .accordion-content' ).removeClass( 'is-active' );
			},

			/**
			 * Scrolls to the Tickets container once the ticket form receives the focus.
			 *
			 * @return {void}
			 */
			'focus.tribe': function() {
				$body.animate( {
					scrollTop: $tickets_container.offset().top - 50
				}, 500 );
			},

			/**
			 * When the edit ticket form fields have completed loading we can setup
			 * other UI features as needed.
			 */
			'edit-tickets-complete.tribe': function() {
				show_hide_ticket_type_history();
			},

			/**
			 * Sets/Swaps out the name & id attributes on Advanced ticket meta fields so we don't have (or submit)
			 * duplicate fields
			 *
			 * We now load these via ajax and there is no need to change field names/IDs
			 *
			 * @deprecated TBD
			 *
			 * @return {void}
			 */
			'set-advanced-fields.tribe': function() {
				var $this            = $( this );
				var $ticket_form     = $this.find( '#ticket_form' );
				var $ticket_advanced = $ticket_form.find( 'tr.ticket_advanced:not(.ticket_advanced_meta)' ).find( 'input, select, textarea' );
				var provider = $ticket_form.find( '.ticket_provider:checked' ).val();

				// for each advanced ticket input, select, and textarea, relocate the name and id fields a bit
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
						$el.closest( 'tr' ).hasClass( 'ticket_advanced_' + provider ) && $el.data( 'name' ) && 0 === $el.attr( 'name' ).length ) {
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
			showButtonPanel: false,
			onChange       : function() {
			},
			onSelect       : function( dateText, inst ) {
				var the_date = $.datepicker.parseDate( 'yy-mm-dd', dateText );
				if ( inst.id === 'ticket_start_date' ) {
					$( document.getElementById( 'ticket_end_date' ) ).datepicker( 'option', 'minDate', the_date );
					if ( the_date ) {
						$( '.ticket_start_time' ).show();
					}
					else {
						$( '.ticket_start_time' ).hide();
					}
				}
				else {
					$( document.getElementById( 'ticket_start_date' ) ).datepicker( 'option', 'maxDate', the_date );
					if ( the_date ) {
						$( '.ticket_end_time' ).show();
					}
					else {
						$( '.ticket_end_time' ).hide();
					}
				}
			}
		};

		$.extend( datepickerOpts, tribe_l10n_datatables.datepicker );

		$( document.getElementById( 'ticket_start_date' ) ).datepicker( datepickerOpts ).keyup( function( e ) {
			if ( e.keyCode === 8 || e.keyCode === 46 ) {
				$.datepicker._clearDate( this );
			}
		} );
		$( document.getElementById( 'ticket_end_date' ) ).datepicker( datepickerOpts ).keyup( function( e ) {
			if ( e.keyCode === 8 || e.keyCode === 46 ) {
				$.datepicker._clearDate( this );
			}
		} );

		/**
		 * Returns the currently selected default ticketing provider.
		 * Defaults to RSVP if something fails
		 *
		 * @since TBD
		 *
		 * @return string
		 */
		function get_default_provider() {
			var $checked_provider = $( '#tribe_panel_settings input[name=default_ticket_provider]' ).filter(':checked');
			return ( $checked_provider.length > 0 ) ? $checked_provider.val() : 'Tribe__Tickets__RSVP';
		}

		/**
		 * Returns the current global capacity (via the settings panel.
		 *
		 * @since TBD
		 *
		 * @return string
		 */
		function get_global_cap() {
			var $global_cap = $( document.getElementById( 'settings_global_capacity_edit' ) )
			return ( $global_cap.length > 0 ) ? $global_cap.val() : '';
		}

		/**
		 * When a ticket type is edited we should (re-)establish the UI for showing
		 * and hiding its history, if it has one.
		 */
		function show_hide_ticket_type_history() {
			var $history = $tribe_tickets.find( '.ticket_advanced.history' );

			if ( ! $history.length ) {
				return;
			}

			var $toggle_link      = $history.find( 'a.toggle-history' );
			var $toggle_link_text = $toggle_link.find( 'span' );
			var $history_list     = $history.find( 'ul' );

			$history.on( 'click', '.toggle-history', function( e ) {
				e.preventDefault();
				if ( $history.hasClass( '_show' ) ) {
					$history.removeClass( '_show' );
				} else {
					$history.addClass( '_show' );
				}

			} );
		}

		function show_panel( e, $panel ) {
			if ( e ) {
				e.preventDefault();
			}

			// this way if we don't pass a panel, it works like a 'reset'
			if ( undefined == $panel ) {
				$panel = $base_panel;
			}
			$panels.each( function() {
				$(this).attr( 'aria-hidden', true );
			} );

			$panel.attr( 'aria-hidden', false );
		}

		/**
		 * Show or hide a panel based on it's current state.
		 * @param e the event that triggered the change
		 * @param $panel obj the panel to be moved
		 *
		 * @since TBD
		 */
		function show_hide_panel( e, $panel ) {
			if ( e ) {
				e.preventDefault();
			}

			if ( undefined !== $panel.attr( 'aria-hidden' ) && 'false' !== $panel.attr( 'aria-hidden' ) ) {
				// we're showing another panel, hide the base first
				if ( $base_panel !== $panel ) {
					$base_panel.attr( 'aria-hidden', 'true' );

				}

				$panel.attr( 'aria-hidden', 'false' );

			} else {

				$panel.attr( 'aria-hidden', 'true' );

				// we're hiding another panel, show the base afterward
				if ( $base_panel !== $panel ) {
					$base_panel.attr( 'aria-hidden', 'false' );
				}
			}
		}

		/**
		 * Shows/hides items based on if we're editing a ticket or RSVP
		 *
		 * @since TBD
		 *
		 * @param e the event that triggered the change
		 * @param int ticket post ID
		 * @return void
		 */
		function change_edit_options( e, $ticket_id ) {
			var is_ticket                    = false;
			var is_edit                      = false;
			var $edit_ticket_title           = $( document.getElementById( 'ticket_title_edit' ) );
			var $add_ticket_title            = $( document.getElementById( 'ticket_title_add' ) );
			var $edit_rsvp_title             = $( document.getElementById( 'rsvp_title_edit' ) );
			var $add_rsvp_title              = $( document.getElementById( 'rsvp_title_add' ) );
			var $ticket_save                 = $( document.getElementById( 'ticket_form_save' ) );
			var $rsvp_save                   = $( document.getElementById( 'rsvp_form_save' ) );
			var $button                      = $( e.target ).closest( 'button' );

			if ( undefined === $ticket_id ) {
				if ( 'ticket_form_toggle' === $button.attr( 'id' ) ) {
					is_ticket = true;
				}
			} else {
				is_edit = true;

				if ( 'Tribe__Tickets__RSVP' !== $button.attr( 'data-provider' ) ) {
					is_ticket = true;
				}
			}

			if ( is_ticket ) {
				$edit_rsvp_title.hide();
				$add_rsvp_title.hide();
				$rsvp_save.hide();
				$ticket_save.show();

				if ( is_edit ) {
					$edit_ticket_title.show();
					$add_ticket_title.hide();
				} else {
					$add_ticket_title.show();
					$edit_ticket_title.hide();
				}
			} else {
				$edit_ticket_title.hide();
				$add_ticket_title.hide();
				$ticket_save.hide();
				$rsvp_save.show();

				if ( is_edit ) {
					$edit_rsvp_title.show();
					$add_rsvp_title.hide();
				} else {
					$add_rsvp_title.show();
					$edit_rsvp_title.hide();
				}
			}
		}

		/**
		 * Refreshes the base and settings panels when we've changed something
		 *
		 * @since TBD
		 *
		 * @param string optional notice to prepend to the ticket table
		 * @param bool (true) flag for panel swap
		 * @return void
		 */
		function refresh_panels( notice, swap ) {
			// make sure we have this for later
			swap = undefined === swap ? true : false;

			var params = {
				action  : 'tribe-ticket-refresh-panels',
				notice: notice,
				post_ID : $( document.getElementById( 'post_ID' ) ).val(),
				nonce   : TribeTickets.add_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					// Ticket table
					if ( response.data.ticket_table && '' != response.data.ticket_table ) {
						// remove old ticket table
						var $ticket_table = $( document.getElementById( 'ticket_list_wrapper' ) );

						if ( 0 === $ticket_table.length ) {
							// if it's not there, create it :(
							var $container = $( '.tribe_sectionheader.ticket_list_container' );
							$ticket_table = $( '<div>', {id: "ticket_list_wrapper"});
							$container.append( $ticket_table );

							if ( $container.hasClass( 'tribe_no_capacity' ) ) {
								$container.removeClass( 'tribe_no_capacity' );
							}
						}

						$ticket_table.empty();
						// create new ticket table (and notice)
						var $new_table = $( '<div>' );
						$new_table.html( response.data.ticket_table );

						// insert new ticket table
						$ticket_table.append( $new_table );
					}

					$tribe_tickets.trigger( 'tribe-tickets-refresh-tables', response.data );
				} ).complete( function( response ) {
					if ( swap ) {
						show_panel();
					}
				}
				);
		}

		/* "Settings" button action */
		$( document.getElementById( 'settings_form_toggle' ) ).on( 'click', function( e ) {
			show_panel( e, $settings_panel);
		} );

		/* Settings "Cancel" button action */
		$( document.getElementById( 'tribe_settings_form_cancel' ) ).on( 'click', function( e ) {
			show_panel( e );
		} );

		/* "Add ticket" button action */
		$( '.ticket_form_toggle' ).on( 'click', function( e ) {
			var $default_provider = get_default_provider();
			var global_cap = get_global_cap();

			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'focus.tribe' );

			if ( 'ticket_form_toggle' === $( this ).attr( 'id' ) ) {
				// Only want to do this if we're setting up a ticket - as opposed to an RSVP

				$( document.getElementById( $default_provider + '_radio' ) ).prop( 'checked', true );
				$( document.getElementById( $default_provider + '_global' ) ).prop( 'checked', true );
				$( document.getElementById( $default_provider + '_global_capacity' ) ).val( global_cap );
				$( document.getElementById( $default_provider + '_global_stock_cap' ) ).attr( 'placeholder', global_cap );

			} else {
				$( document.getElementById( 'Tribe__Tickets__RSVP_radio' ) ).prop( 'checked', true );
			}

			$( document.getElementById( 'tribe_panel_edit' ) ).find( '.tribe-dependency' ).trigger( 'verify.dependency' );

			$( document.getElementById( 'tribe_show_ticket_description' ) ).prop( 'checked', true );

			$tribe_tickets.trigger( 'ticket-provider-changed.tribe' );

			// WE have to trigger this after the verify.dependency
			if ( 'ticket_form_toggle' === $( this ).attr( 'id' ) && undefined !== global_cap && 0 < global_cap ) {
				$( document.getElementById( $default_provider + '_global_capacity' ) ).prop( 'disabled', true );
			}

			change_edit_options( e );

			show_panel( e, $edit_panel );
		} );

		/* Ticket "Cancel" button action */
		$( document.getElementById( 'ticket_form_cancel' ) ).on( 'click', function( e ) {
			show_panel( e );

			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'set-advanced-fields.tribe' )
				.trigger( 'focus.tribe' );
		} );

		/* Change global stock type if we put a value in global_stock_cap */
		$( document ).on( 'blur', '[name="global_stock_cap"]', function( e ) {
			var $this = $( this );
			var $global_field = $this.closest( 'fieldset' ).find( '[name="ticket_global_stock"]' );
			var global_field_val = 'global';

			if ( 0 < $this.val() ) {
				global_field_val = 'capped';
			}

			$global_field.val( global_field_val );
		} );

		/* "Save Ticket" button action */
		$( document.getElementById( 'ticket_form_save' ) ).add( $( document.getElementById( 'rsvp_form_save' ) ) ).on( 'click', function( e ) {
			var $form = $( document.getElementById( 'ticket_form_table' ) );
			var type  = $form.find( '.ticket_provider:checked' ).val();

			$tribe_tickets.trigger( 'save-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

			var form_data = $form.find( '.ticket_field' ).serialize();

			var params = {
				action  : 'tribe-ticket-add-' + $( 'input[name=ticket_provider]:checked' ).val(),
				formdata: form_data,
				post_ID : $( document.getElementById( 'post_ID' ) ).val(),
				nonce   : TribeTickets.add_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					$tribe_tickets.trigger( 'saved-ticket.tribe', response );

					if ( response.success ) {
						refresh_panels( 'ticket' );
					}
				},
				'json'
			).complete( function() {
				$tribe_tickets.trigger( 'spin.tribe', 'stop' ).trigger( 'focus.tribe' );
			} );

		} );

		/* "Delete Ticket" link action */
		$tribe_tickets.on( 'click', '.ticket_delete', function( e ) {
			if ( ! confirm( tribe_ticket_notices.confirm_alert ) ) {
				return false;
			}

			e.preventDefault();

			$tribe_tickets.trigger( 'delete-ticket.tribe', e ).trigger( 'spin.tribe', 'start' );

			var deleted_ticket_id = $( this ).attr( 'attr-ticket-id' );

			var params = {
				action   : 'tribe-ticket-delete-' + $( this ).attr( 'attr-provider' ),
				post_ID  : $( document.getElementById( 'post_ID' ) ).val(),
				ticket_id: deleted_ticket_id,
				nonce    : TribeTickets.remove_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					$tribe_tickets.trigger( 'deleted-ticket.tribe', response );

					if ( response.success ) {
						// remove deleted ticket from table
						var $deleted_row = $( document.getElementById( 'tribe_ticket_list_table' ) ).find( '[data-ticket-order-id="order_' + deleted_ticket_id + '"]' );
						$deleted_row.remove();

						refresh_panels( 'delete' );

						show_panel( e );
					}
				},
				'json'
			).complete( function() {
				$tribe_tickets.trigger( 'spin.tribe', 'stop' );
			} );
		} );

		/* "Edit Ticket" link action */
		$tribe_tickets.on( 'click', '.ticket_edit_button', function( e ) {

				e.preventDefault();

				$tribe_tickets.trigger( 'spin.tribe', 'start' );

				var ticket_id = this.getAttribute( 'data-ticket-id' );

				var params = {
					action   : 'tribe-ticket-edit-' + this.getAttribute( 'data-provider' ),
					post_ID  : $( document.getElementById( 'post_ID' ) ).val(),
					ticket_id: ticket_id,
					nonce    : TribeTickets.edit_ticket_nonce
				};

				change_edit_options( e, ticket_id );

				$.post(
					ajaxurl,
					params,
					function( response ) {

						if ( ! response ) {
							return;
						}

						var regularPrice = response.data.price;
						var salePrice    = regularPrice;
						var onSale       = false;

						if ( 'undefined' !== typeof response.data.on_sale && response.data.on_sale ) {
							onSale       = true;
							regularPrice = response.data.regular_price;
						}

						// trigger a change event on the provider radio input so the advanced fields can be re-initialized
						$( 'input:radio[name=ticket_provider]' ).filter( '[value=' + response.data.provider_class + ']' ).click();
						$( 'input[name=ticket_provider]:radio' ).change();

						console.log(response.data);

						// Capacity/Stock
						if ( response.data.global_stock_mode ) {
							switch ( response.data.global_stock_mode ) {
								case 'global':
								case 'capped':
									$( document.getElementById( response.data.provider_class + '_global' ) ).prop( 'checked', true );
									$( document.getElementById( response.data.provider_class + '_global_capacity' ) ).val( response.data.total_global_stock ).prop('disabled', true);
									$( document.getElementById( response.data.provider_class + '_global_stock_cap' ) ).attr( 'placeholder', response.data.total_global_stock);

									if ( undefined !== response.data.global_stock_cap && $.isNumeric( response.data.global_stock_cap ) && 0 < response.data.global_stock_cap ) {
										$( document.getElementById( response.data.provider_class + '_global' ) ).val( 'capped' );
										$( document.getElementById( response.data.provider_class + '_global_stock_cap' ) ).val( response.data.global_stock_cap );
									} else {
										$( document.getElementById( response.data.provider_class + '_global' ) ).val( 'global' );
										$( document.getElementById( response.data.provider_class + '_global_stock_cap' ) ).val( '' );
									}
									break;
								case 'own':
									$( document.getElementById( response.data.provider_class + '_own' ) ).prop( 'checked', true );
									$( document.getElementById( response.data.provider_class + '_capacity' ) ).val( response.data.stock );
									break;
								default:
									// Just in case
									$( document.getElementById( response.data.provider_class + '_unlimited' ) ).prop( 'checked', true );
									$( document.getElementById( response.data.provider_class + '_global_stock_cap' ) ).val( '' );
							}
						} else {
							$( document.getElementById( response.data.provider_class + '_unlimited' ) ).prop( 'checked', true );
							$( document.querySelectorAll( '.ticket_stock' ) ).val( response.data.original_stock );
						}

						$( 'input[name=ticket_global_stock]:radio' ).change();

						$( document.getElementById( 'ticket_id' ) ).val( response.data.ID );
						$( document.getElementById( 'ticket_name' ) ).val( response.data.name );
						$( document.getElementById( 'ticket_description' ) ).val( response.data.description );

						// Compare against 0 for backwards compatibility.
						if ( 0 === parseInt( response.data.show_description ) ) {
							$( document.getElementById( 'tribe_show_ticket_description' ) ).prop( 'checked', true );
						} else {
							$( document.getElementById( 'tribe_show_ticket_description' ) ).removeAttr( 'checked' );
						}


						var start_date = response.data.start_date.substring( 0, 10 );
						var end_date = response.data.end_date.substring( 0, 10 );

						// handle all the date stuff
						$( document.getElementById( 'ticket_start_date' ) ).val( start_date );
						$( document.getElementById( 'ticket_end_date' ) ).val( end_date );

						// @TODO: it's "meridiem" not "meridian"
						var $start_meridian = $( document.getElementById( 'ticket_start_meridian' ) );
						var $end_meridian   = $( document.getElementById( 'ticket_end_meridian' ) );

						if ( response.data.start_date ) {
							var start_hour = parseInt( response.data.start_date.substring( 11, 13 ) );
							var start_meridian = 'am';

							if ( start_hour > 12 ) {
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

							$( document.getElementById( 'ticket_start_hour' ) ).val( start_hour ).trigger( 'change' );
							$( document.getElementById( 'ticket_start_minute' ) ).val( response.data.start_date.substring( 14, 16 ) ).trigger( 'change' );
							$( document.getElementById( 'ticket_start_meridian' ) ).val( start_meridian ).trigger( 'change' );
						}

						if ( response.data.end_date ) {
							var end_hour     = parseInt( response.data.end_date.substring( 11, 13 ) );
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

							$( document.getElementById( 'ticket_end_hour' ) ).val( end_hour ).trigger( 'change' );
							$( document.getElementById( 'ticket_end_minute' ) ).val( response.data.end_date.substring( 14, 16 ) ).trigger( 'change' );
							$( document.getElementById( 'ticket_end_meridian' ) ).val( end_meridian ).trigger( 'change' );

							$( '.ticket_end_time' ).show();
						}

						$( document.getElementById( response.data.provider_class + '_advanced' ) ).replaceWith( response.data.advanced_fields );

						// set the prices
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

						var $sale_field     = $( document.getElementById( 'ticket_sale_price' ) );
						var $sale_container = $sale_field.closest( '.input_block' )

						if ( onSale ) {
							$sale_field.prop( 'readonly', false ).val( salePrice ).prop( 'readonly', true );
							$sale_container.show();
						} else {
							$sale_container.hide();
						}

						if ( 'undefined' !== typeof response.data.purchase_limit && response.data.purchase_limit ) {
							$( document.getElementById( 'ticket_purchase_limit' ) ).val( response.data.purchase_limit );
						}

						if ( response.data.sku ) {
							$( document.querySelectorAll( '.sku_input' ) ).val( response.data.sku );
						}

						if ( 'undefined' !== typeof response.data.controls && response.data.controls ) {
							$( document.getElementById( 'ticket_bottom_right' ) ).html( response.data.controls );
						}

						$tribe_tickets.find( '.tribe-bumpdown-trigger' ).bumpdown();

						$( 'a#ticket_form_toggle' ).hide();

						$( document.getElementById( 'tribe_panel_edit' ) ).find( '.tribe-dependency' ).trigger( 'verify.dependency' );
					},
					'json'
				).always( function( response ) {
					$tribe_tickets
						.trigger( 'spin.tribe', 'stop' )
						.trigger( 'focus.tribe' )
						.trigger( 'edit-tickets-complete.tribe' );

					$( document.getElementById( 'tribe_panel_edit' ) ).find( '.tribe-dependency' ).trigger( 'verify.dependency' );

					if ( response.data.total_global_stock ) {
						$( document.getElementById( response.data.provider_class + '_global_capacity' ) ).prop('disabled', true);
					}

					show_panel( e, $edit_panel );
				} );

			} )
			.on( 'keyup', '#ticket_price', function ( e ) {
				e.preventDefault();

				var decimal_point = price_format.decimal;
				var regex         = new RegExp( '[^\-0-9\%\\' + decimal_point + ']+', 'gi' );
				var value         = $( this ).val();
				var newvalue      = value.replace( regex, '' );

				// @todo add info message or tooltip to let people know we are removing the comma or period
				if ( value !== newvalue ) {
					$( this ).val( newvalue );
				}
			} )
			.on( 'click', '#tribe_ticket_header_image, #tribe_ticket_header_preview', function( e ) {
				e.preventDefault();
				ticketHeaderImage.uploader( '', '' );
			} )
			.on( 'click', '#tribe_settings_form_save', function( e ) {
				e.preventDefault();
				var $settings_form = $( document.getElementById( 'tribe_panel_settings' ) );
				var form_data = $settings_form.find( '.settings_field' ).serialize();
				var $global_capacity = $( document.getElementById( 'settings_global_capacity_edit' ) );

				if ( false === $global_capacity.prop( 'disabled' ) ) {
					$global_capacity.blur();
					$global_capacity.prop( 'disabled', true );
				}

				var params = {
					action  : 'tribe-ticket-save-settings',
					formdata: form_data,
					post_ID : $( document.getElementById( 'post_ID' ) ).val(),
					nonce   : TribeTickets.add_ticket_nonce
				};

				$.post(
					ajaxurl,
					params,
					function( response ) {
						$tribe_tickets.trigger( 'saved-image.tribe', response );
						if ( response.success ) {
							refresh_panels( 'settings' );
						}
					},
					'json'
				);
			} )
			;

		var $remove  = $( document.getElementById( 'tribe_ticket_header_remove' ) );
		var $preview = $( document.getElementById( 'tribe_ticket_header_preview' ) );

		if ( $preview.find( 'img' ).length ) {
			$remove.show();
		}

		/**
		 * Track changes to the global stock level. Changes to the global stock
		 * checkbox itself is handled elsewhere.
		 */
		$global_stock_level.change( function() {
			global_stock_setting_changed = true;
		} );

		/**
		 * Unset the global stock settings changed flag if the post is being
		 * saved/updated (no need to trigger a confirmation dialog in these
		 * cases).
		 */
		$( 'input[type="submit"]' ).click( function() {
			global_stock_setting_changed = false;
		} );

		/**
		 * If the user attempts to nav away without saving global stock setting
		 * changes then try to bring this to their attention!
		 */
		$( window ).on( 'beforeunload', function() {
			// If the global stock settings have not changed, do not interfere
			if ( ! global_stock_setting_changed ) {
				return;
			}

			// We can't trigger a confirm() dialog from within this action but returning
			// a string should achieve effectively the same result
			return tribe_global_stock_admin_ui.nav_away_msg;

		} );

		/* Handle editing global capacity from the settings panel */
		$( document ).on( 'click', '.global_capacity_edit_button', function( e ) {
			e.preventDefault();
			$( document.getElementById( 'settings_global_capacity_edit' ) ).prop( 'disabled', false ).focus();
		} );

		$( document ).on( 'blur', '#settings_global_capacity_edit', function() {
			var capacity = $( this ).val();

			var params = {
				action   : 'tribe-events-edit-global-capacity',
				post_ID  : $( document.getElementById( 'post_ID' ) ).val(),
				capacity : capacity,
				nonce    : TribeTickets.edit_ticket_nonce
			};

			$.post(
				ajaxurl,
				params,
				function( response ) {
					refresh_panels( null, false );
				} );
		} );

		$( 'body' ).on( 'click', '#tribe_ticket_header_remove', function( e ) {

			e.preventDefault();
			$preview.html( '' );
			$remove.hide();
			$( document.getElementById( 'tribe_ticket_header_image_id' ) ).val( '' );

		} );

		if ( $( document.getElementById( 'tribe_ticket_header_preview img' ) ).length ) {

			var $tiximg = $( document.getElementById( 'tribe_ticket_header_preview img' ) );
			$tiximg.removeAttr( 'width' ).removeAttr( 'height' );

			if ( $tribe_tickets.width() < $tiximg.width() ) {
				$tiximg.css( 'width', '95%' );
			}
		}
	} );

} )( window, jQuery );
