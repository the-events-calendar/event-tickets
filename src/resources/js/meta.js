(function ( window, document, $ ) {

	$( '#event_tickets' ).on('change','input.show_attendee_info', function() {

		var attendee_form = $(this).parents('tr').next('tr.tribe-tickets-attendee-info-form');
		if(this.checked) {
			attendee_form.show();
		}else {
			attendee_form.hide();
		}
	} ).on('change', '.save_attendee_fieldset', function() {
		var fieldset_name = $('.tribe-tickets-attendee-saved-fieldset-name');
		if(this.checked) {
			fieldset_name.show();
		} else {
			fieldset_name.hide();
		}
	});

	$( "#tribe-tickets-attendee-sortables" ).sortable( {
		containment: "parent",
		items: "> div",
		tolerance: "pointer",
		connectWith: '#tribe-tickets-attendee-sortables'
	} );

	// hide the saved fields selection if there are active fields
	function maybe_hide_saved_fields_select() {
		if ( $( '.tribe-tickets-attendee-info-active-field' ).length ) {
			$('.tribe-tickets-attendee-saved-fields' ).hide();
		} else {
			$('.tribe-tickets-attendee-saved-fields' ).show();
		}
	}
	maybe_hide_saved_fields_select();


	//load saved fields
	$( '#tribetickets' ).on( 'change', '.ticket-attendee-info-dropdown', function () {
		var selected_attendee_id = $( this ).val();

		if ( selected_attendee_id && selected_attendee_id != '0' ) {
			//load the saved fieldset
			var args = {
				action: 'tribe-tickets-load-saved-fields',
				fieldset: selected_attendee_id
			};
			$.post(
				ajaxurl,
				args,
				function ( response ) {
					if ( response.success ) {
						$( '#tribe-tickets-attendee-sortables' ).append( response.data );
						maybe_hide_saved_fields_select();
					}
				},
				'json'
			);
		}
		else {

		}
	} ).on( 'click', '.postbox .hndle, .postbox .handlediv', function () {
		var p = $( this ).parent( '.postbox' ), id = p.attr( 'id' );

		p.toggleClass( 'closed' );

		if ( id ) {
			if ( !p.hasClass( 'closed' ) && $.isFunction( postboxes.pbshow ) )
				self.pbshow( id );
			else if ( p.hasClass( 'closed' ) && $.isFunction( postboxes.pbhide ) )
				self.pbhide( id );
		}

	} ).on( 'click', 'a.add-attendee-field', function ( e ) {

		e.preventDefault();
		var $this = $( this );

		var args = {
			action: 'tribe-tickets-info-render-field',
			type: $this.attr( 'data-type' )
		};

		$.post(
			ajaxurl,
			args,
			function ( response ) {
				if ( response.success ) {
					$( '#tribe-tickets-attendee-sortables' ).append( response.data );
					maybe_hide_saved_fields_select();

				}
			},
			'json'
		);


	} ).on( 'click', 'a.delete-attendee-field', function ( e ) {
		e.preventDefault();
		$( this ).parent().parent().parent().remove();

		maybe_hide_saved_fields_select();

	} ).on( 'edit-ticket.tribe', function () {
		$( 'input.show_attendee_info:checked' ).each( function () {
			$( this ).parents( 'tr' ).next( 'tr.tribe-tickets-attendee-info-form' ).show();
		} );
		$( "#tribe-tickets-attendee-sortables" ).sortable( {
			containment: "parent",
			items: "> div",
			tolerance: "pointer",
			connectWith: '#tribe-tickets-attendee-sortables'
		} );

		maybe_hide_saved_fields_select();

	} );



})( window, document, jQuery );

