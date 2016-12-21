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
			$enable_global_stock = $( "#tribe-tickets-enable-global-stock" ),
			$global_stock_level = $( "#tribe-tickets-global-stock-level" ),
			global_stock_setting_changed = false,
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
					$ticket_form = $this.find( '#ticket_form'),
					$ticket_settings = $ticket_form.find( "tr:not(.event-wide-settings)" );

				$this.find( 'a#ticket_form_toggle' ).show();

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

				$this.find( '.ticket_start_time, .ticket_end_time, .ticket.sale_price' ).hide();

				$this.find( '#ticket_price' ).removeProp( 'disabled' )
					.siblings( '.no-update-message' ).html( '' ).hide()
					.end().siblings( '.description' ).show();

				$('#tribe-tickets-attendee-sortables').empty();
				$('.tribe-tickets-attendee-saved-fields').show();

				$ticket_form.hide();
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
				var $this = $( this );
				var $ticket_form = $this.find( '#ticket_form' );
				var $ticket_advanced = $ticket_form.find( 'tr.ticket_advanced:not(.ticket_advanced_meta)' ).find( 'input, select, textarea' );
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

				// (Re-)set the global stock fields
				$tribe_tickets.trigger( 'set-global-stock-fields.tribe' );

				// Also reset each time the global stock mode selector is changed
				$( '#ticket_global_stock' ).change( function() {
					$tribe_tickets.trigger( 'set-global-stock-fields.tribe' );
				});
			},

			'set-global-stock-fields.tribe': function() {
				var provider_class   = currently_selected_provider();
				var $provider_fields = $( this ).find( '#ticket_form').find( '.ticket_advanced_' + provider_class );

				if ( $provider_fields.length < 1 ) {
					return;
				}

				var $normal_stock_field  = $provider_fields.filter( '.stock' );
				var $global_stock_fields = $provider_fields.filter( '.global-stock-mode' );
				var $sales_cap_field     = $global_stock_fields.filter( '.sales-cap-field' );

				var mode     = $( '#ticket_global_stock' ).val();
				var enabled  = global_stock_enabled();

				// Show or hide global (and normal, "per-ticket") stock settings as appropriate
				$global_stock_level.toggle( enabled );
				$global_stock_fields.toggle( global_stock_enabled() );
				$normal_stock_field.toggle( ! enabled );

				// If global stock is not enabled we need go no further
				if ( ! enabled ) {
					return;
				}

				// Otherwise, toggle on and off the relevant stock quantity fields
				switch ( mode ) {
					case "global":
						$sales_cap_field.hide();
						$normal_stock_field.hide();
						break;
					case "capped":
						$sales_cap_field.show();
						$normal_stock_field.hide();
						break;
					case "own":
						$sales_cap_field.hide();
						$normal_stock_field.show();
						break;
				}
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

		$.extend( datepickerOpts, tribe_l10n_datatables.datepicker );

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

		/**
		 * Indicates if the "enable global stock" field has been checked.
		 *
		 * @returns boolean
		 */
		function global_stock_enabled() {
			return $enable_global_stock.prop( "checked" );
		}

		/**
		 * Show or hide global stock fields and settings as appropriate.
		 */
		function show_hide_global_stock() {
			global_stock_setting_changed = true;
			$tribe_tickets.trigger( 'set-global-stock-fields.tribe' );
		}

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
			return ( $checked_provider.length > 0 )
				? $checked_provider[0].value
				: "";
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

			var $toggle_link = $history.find( 'a.toggle-history' );
			var $toggle_link_text = $toggle_link.find( 'span' );
			var $history_list = $history.find( 'ul' );

			$history.find( 'a.toggle-history' ).click( function( event ) {
				$toggle_link_text.toggle();
				$history_list.toggle();
				event.stopPropagation();
				return false;
			} );
		}

		// Show or hide the global stock level as appropriate, both initially and thereafter
		$enable_global_stock.change( show_hide_global_stock );
		$enable_global_stock.trigger( 'change' );

		// Triggering a change event will falsely set the global_stock_setting_changed flag to
		// true - undo this as it is a one-time false positive
		global_stock_setting_changed = false;

		/* Show the advanced metabox for the selected provider and hide the others on selection change */
		$( 'input[name=ticket_provider]:radio' ).change( function() {
			show_hide_advanced_fields();
		} );

		/* Show the advanced metabox for the selected provider and hide the others at ready */
		$( 'input[name=ticket_provider]:checked' ).each( function() {
			show_hide_advanced_fields();
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
			$( document.getElementById( 'tribetickets' ) ).trigger( 'ticket-provider-changed.tribe' );
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
				$rows = $form.find( '.ticket, .ticket_advanced_meta, .ticket_advanced_' + type );

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
						$( 'td.ticket_list_container' ).empty().html( response.data.html );
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
			if ( ! confirm( tribe_ticket_notices.confirm_alert ) ) {
				return false;
			}

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

						$tribe_tickets.find( '.tribe-bumpdown-trigger' ).bumpdown();

						$( 'a#ticket_form_toggle' ).hide();
						$( '#ticket_form' ).show();

						$tribe_tickets
							.trigger( 'set-advanced-fields.tribe' )
							.trigger( 'edit-ticket.tribe', response );

					},
					'json'
				).complete( function() {
					$tribe_tickets
						.trigger( 'spin.tribe', 'stop' )
						.trigger( 'focus.tribe' )
						.trigger( 'edit-tickets-complete.tribe' );
				} );

			} )
			.on( 'click', '#tribe_ticket_header_image', function( e ) {
				e.preventDefault();
				ticketHeaderImage.uploader( '', '' );
			} )
			.on( 'keyup', '#ticket_price', function ( e ) {
				e.preventDefault();

				var regex;
				var decimal_point = price_format.decimal;

				regex = new RegExp( '[^\-0-9\%\\' + decimal_point + ']+', 'gi' );

				var value = $( this ).val();
				var newvalue = value.replace( regex, '' );

				// @todo add info message or tooltip to let people know we are removing the comma or period
				if ( value !== newvalue ) {
					$( this ).val( newvalue );
				}
			} );


		var $remove = $( '#tribe_ticket_header_remove' );
		var $preview = $( '#tribe_ticket_header_preview' );

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
