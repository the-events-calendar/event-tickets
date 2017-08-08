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
	var $base_panel                  = $( document.getElementById( 'tribe_panel_base' ) );
	var $edit_panel                  = $( document.getElementById( 'tribe_panel_edit' ) );
	var $settings_panel              = $( document.getElementById( 'tribe_panel_settings' ) );
	var $edit_titles                 = $( '.ticket_form_title_edit' );
	var $add_titles                  = $( '.ticket_form_title_add' );

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
				var $this            = $( this );
				var $ticket_form     = $this.find( '#ticket_form' );
				var $ticket_settings = $ticket_form.find( "div:not(.event-wide-settings)" );

				$ticket_settings.find( 'input:not(:button):not(:radio):not(:checkbox):not([type="hidden"]), textarea' ).val( '' );
				$ticket_settings.find( 'input:checkbox' ).attr( 'checked', false );
				$ticket_settings.find( '#ticket_id' ).val( '' );

				$this.find( '#ticket_form input[name="show_attendee_info"]' ).prop( 'checked', false ).change();

				// some fields may have a default value we don't want to lose after clearing the form
				$this.find( 'input[data-default-value]' ).each( function() {
					var $current_field = $( this );
					$current_field.val( $current_field.data( 'default-value' ) );
				} );

				// Reset the min/max datepicker settings so that they aren't inherited by the next ticket that is edited
				$this.find( '#ticket_start_date' ).datepicker( 'option', 'maxDate', null );
				$this.find( '#ticket_end_date' ).datepicker( 'option', 'minDate', null );

				$this.find( '#ticket_price' ).removeProp( 'disabled' )
					.siblings( '.no-update-message' ).html( '' ).hide()
					.end().siblings( '.description' ).show();

				$( document.getElementById( 'tribe-tickets-attendee-sortables' ) ).empty();
				$( '.tribe-tickets-attendee-saved-fields' ).show();

				$( document.getElementById( 'ticket_bottom_right' ) ).empty();

				$edit_titles.hide();
				$add_titles.show();

				$ticket_form.find( '.accordion-header, .accordion-content' ).removeClass( 'is-active' );
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
		 * Show or hide the appropriate set of provider-specific fields.
		 */
		function show_hide_advanced_fields() {
			$( 'tr.ticket_advanced' ).hide();
			$( 'tr.ticket_advanced_' + currently_selected_provider() + ':not(.sale_price)' ).show();
			$tribe_tickets.trigger( 'set-advanced-fields.tribe' );
			$( document.getElementById( 'tribetickets' ) ).trigger( 'ticket-provider-changed.tribe' );
		}

		/**
		 * Returns the currently selected ticketing provider.
		 *
		 * @return string
		 */
		function currently_selected_provider() {
			var $checked_provider = $( 'input[name="ticket_provider"]:checked' );
			return ( $checked_provider.length > 0 ) ? $checked_provider[0].value : "";
		}

		/**
		 * When a ticket type is edited we should (re-)establish the UI for showing
		 * and hiding its history, if it has one.
		 */
		function show_hide_ticket_type_history() {
			var $history = $tribe_tickets.find( 'tr.ticket_advanced.history' );

			if ( ! $history.length ) {
				return;
			}

			var $toggle_link      = $history.find( 'a.toggle-history' );
			var $toggle_link_text = $toggle_link.find( 'span' );
			var $history_list     = $history.find( 'ul' );

			$history.find( 'a.toggle-history' ).click( function( event ) {
				$toggle_link_text.toggle();
				$history_list.toggle();
				event.stopPropagation();
				return false;
			} );
		}

		/**
		 * Show or hide a panel based on it's current state.
		 * @param e the event that triggered the change
		 * @param $panel obj the panel to be moved
		 *
		 * @since TBD
		 */
		function show_hide_panel( e, $panel ) {
			e.preventDefault();

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

		/* "Settings" button action */
		$( document.getElementById( 'settings_form_toggle' ) ).on( 'click', function( e ) {
			show_hide_panel( e, $settings_panel);
		} );

		/* Settings "Cancel" button action */
		$( document.getElementById( 'tribe_settings_form_cancel' ) ).on( 'click', function( e ) {
			show_hide_panel( e, $settings_panel);
		} );

		/* "Add a ticket" link action */
		$( '.ticket_form_toggle' ).on( 'click', function( e ) {
			show_hide_panel( e, $edit_panel );

			if ( 'ticket_form_toggle' === $( this ).attr( 'id' ) ) {
				// uncheck them all!
				$( '.ticket_provider' ).each( function() {
					$( this ).prop( 'checked', true ).removeAttr( 'checked' );
				});
				if ( $( document.getElementById( 'Tribe__Tickets_Plus__Commerce__EDD__Main_radio' ) ) ) {
					$( document.getElementById( 'Tribe__Tickets_Plus__Commerce__EDD__Main_radio' ) ).prop( 'checked', true );
				} else {
					$( document.getElementById( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main_radio' ) ).prop( 'checked', true );
				}

			} else {
				$( document.getElementById( 'Tribe__Tickets__RSVP_radio' ) ).prop( 'checked', true );
			}

			$( document.getElementById( 'ticket_form_main' ) ).find( '.tribe-dependency' ).trigger( 'verify.dependency' );

			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'set-advanced-fields.tribe' )
				.trigger( 'focus.tribe' );
			$( document.getElementById( 'tribetickets' ) ).trigger( 'ticket-provider-changed.tribe' );
		} );

		/* "Cancel" button action */
		$( document.getElementById( 'ticket_form_cancel' ) ).on( 'click', function( e ) {
			show_hide_panel( e, $edit_panel );

			$tribe_tickets
				.trigger( 'clear.tribe' )
				.trigger( 'set-advanced-fields.tribe' )
				.trigger( 'focus.tribe' );
		} );

		/* Change global stock type if we put a value in #ticket_woo_global_stock_cap */
		$( document.getElementById( 'ticket_woo_global_stock_cap' ) ).on( 'blur', function( e ) {
			var $global = $( document.getElementById( 'global' ) );
			if ( 0 < $( this ).val() ) {
				$global.val( 'capped' );
			} else {
				$global.val( 'global' );
			}
		} );

		/* "Save Ticket" button action */
		$( document.getElementById( 'ticket_form_save' ) ).click( function( e ) {
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
						var responseData = response.data.data ;

						// Get the original (pre-save) capacity 'field' in the ticket table and set it to our new capacity
						var original_capacity_base = $( document.getElementById( 'original_capacity__' + responseData.ticket_id ) );
						original_capacity_base.text( responseData.ticket_capacity );
						// Get the original (pre-save) available capacity 'field' in the ticket table and set it to our new available capacity
						var available_capacity_base = $( document.getElementById( 'available_capacity__' + responseData.ticket_id ) );
						available_capacity_base.text( responseData.ticket_stock );
						// Change the available capacity in the editor
						$( document.getElementById( 'rsvp_ticket_stock_total_value' ) ).text( responseData.ticket_stock );

						// remove old ticket table
						var $ticket_table = $( document.getElementById( 'ticket_list_wrapper' ) );

						if ( 0 === $ticket_table.length ) {
							// if it's not there, create it :(
							var $container = $( '.tribe_sectionheader.ticket_list_container' );
							$ticket_table = $( '<div>', {id: "ticket_list_wrapper"});
							container.append( ticket_table );

							if ( container.hasClass( 'tribe_no_capacity' ) ) {
								container.removeClass( 'tribe_no_capacity' );
							}
						}

						$ticket_table.empty();
						// create new ticket table (and notice)
						var $new_table = $( '<div>' );
						$new_table.html( response.data.html );

						// insert new ticket table
						$ticket_table.append( $new_table );

						show_hide_panel( e, $edit_panel );
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

						show_hide_panel( e, $edit_panel );
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

				var params = {
					action   : 'tribe-ticket-edit-' + this.getAttribute( 'data-provider' ),
					post_ID  : $( document.getElementById( 'post_ID' ) ).val(),
					ticket_id: this.getAttribute( 'data-ticket-id' ),
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

						if ( response.data.ID ) {
							$edit_titles.show();
							$add_titles.hide();
						}

						// Capacity/Stock
						if ( response.data.global_stock_mode ) {
							switch ( response.data.global_stock_mode ) {
								case 'global':
									$( document.getElementById( 'global' ) ).prop( 'checked', true );
									$( document.querySelectorAll( '.global_stock_cap' ) ).val( response.data.global_stock_cap );
									// this one is _not_ working :(
									$( document.getElementById( 'tribe-tickets-global-stock-input' ) ).val( response.data.total_global_stock );
									break;
								case 'own':
									$( document.getElementById( 'own' ) ).prop( 'checked', true );
									$( document.querySelectorAll( '.ticket_stock' ) ).val( response.data.stock );
									break;
								default:
									$( document.getElementById( 'unlimited' ) ).prop( 'checked', true );
							}
						} else {
							$( document.querySelectorAll( '.ticket_stock' ) ).val( response.data.original_stock );
							$( document.querySelectorAll( '.ticket_stock_total_value' ) ).text( response.data.stock );
						}

						$( document.getElementById( 'ticket_id' ) ).val( response.data.ID );
						$( document.getElementById( 'ticket_name' ) ).val( response.data.name );
						$( document.getElementById( 'ticket_description' ) ).val( response.data.description );

						$( document.getElementById( 'tribe-tickets-global-stock' ) ).val( response.data.global_stock );
						$( document.getElementById( 'ticket_woo_global_stock_cap' ) ).val( response.data.global_stock_cap );
						$( document.getElementById( 'ticket_edd_global_stock_cap' ) ).val( response.data.global_stock_cap );

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

							$( document.getElementById( 'ticket_start_hour' ) ).val( start_hour ).trigger( "change" );
							$( document.getElementById( 'ticket_start_minute' ) ).val( response.data.start_date.substring( 14, 16 ) ).trigger( "change" );
							$( document.getElementById( 'ticket_start_meridian' ) ).val( start_meridian ).trigger( "change" );
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

							$( document.getElementById( 'ticket_end_hour' ) ).val( end_hour ).trigger( "change" );
							$( document.getElementById( 'ticket_end_minute' ) ).val( response.data.end_date.substring( 14, 16 ) ).trigger( "change" );
							$( document.getElementById( 'ticket_end_meridian' ) ).val( end_meridian ).trigger( "change" );

							$( '.ticket_end_time' ).show();
						}

						var $ticket_advanced = $( 'tr.ticket_advanced input' );
						$ticket_advanced.data( 'name', $ticket_advanced.attr( 'name' ) ).attr( {
							'name': '',
							'id': ''
						} );
						$( 'tr.ticket_advanced' ).remove();
						$( 'tr.ticket.bottom' ).before( response.data.advanced_fields );

						// trigger a change event on the provider radio input so the advanced fields can be re-initialized
						$( 'input:radio[name=ticket_provider]' ).filter( '[value=' + response.data.provider_class + ']' ).click();
						$( 'input[name=ticket_provider]:radio' ).change();

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

						var $sale_field = $( document.getElementById( 'ticket_sale_price' ) );

						if ( onSale ) {
							$sale_field.prop( 'readonly', false ).val( salePrice ).prop( 'readonly', true );
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

						$tribe_tickets
							.trigger( 'set-advanced-fields.tribe' )
							.trigger( 'edit-ticket.tribe', response );

					},
					'json'
				).complete( function( response ) {
					$tribe_tickets
						.trigger( 'spin.tribe', 'stop' )
						.trigger( 'focus.tribe' )
						.trigger( 'edit-tickets-complete.tribe' );

					show_hide_panel( e, $edit_panel );
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
							show_hide_panel( e, $settings_panel );
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
