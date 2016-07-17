/**
 * Handles the move tickets/move ticket type dialogs.
 *
 * @var object tribe_move_tickets
 * @var object tribe_move_tickets_data
 */

var tribe_move_ticket_type_dialog = ( 'object' === typeof tribe_move_ticket_type_dialog ) ? tribe_move_ticket_type_dialog : {};

( function( $, obj ) {
	var $main,
	    $post_selector,
	    $post_type,
	    $search_terms,
	    $move_btn,
	    $spinner,
	    wait_key_search,
	    working = false,
	    queued_update = false;


	function init() {
		$main           = $( '#main' );
		$post_type      = $( '#post-type' );
		$search_terms   = $( '#search-terms' );
		$move_btn       = $( '#move-ticket-type' );
		$spinner        = $( '#spinner' );
		$post_selector  = $main.find( '.select-single-container' );
		$spinner.hide();
		$post_type.change( on_filter_change );
		$search_terms.keyup( search_terms_changed );
		$post_selector.click( on_post_selection );
		$move_btn.click( do_move );
	}

	/**
	 * Wait until the user stops typing, then initiate an update of the
	 * post list.
	 */
	function search_terms_changed() {
		// User hasn't finished typing? Clear existing timeout so we can reset
		if ( wait_key_search ) {
			clearTimeout( wait_key_search );
		}

		wait_key_search = setTimeout( on_filter_change, 100 );
	}

	/**
	 * If the post type or search term fields change, update the post
	 * choice list accordingly.
	 */
	function on_filter_change() {
		// Do nothing if a request is already in progress
		if ( working ) {
			queued_update = true;
			return;
		}

		working = true;
		$move_btn.prop( 'disabled', true );
		$post_selector.addClass( 'updating' );

		var request = {
			'action':       'move_ticket_types_post_list',
			'check':        tribe_move_tickets_data.check,
			'post_type':    $post_type.find( ':selected' ).attr( 'name' ),
			'ignore':       tribe_move_tickets_data.src_post_id,
			'search_terms': $search_terms.val()
		};

		$.post( ajaxurl, request, update_post_selection, 'json' );
	}

	/**
	 * Verify the response and update the list of posts available for selection.
	 *
	 * @param data
	 */
	function update_post_selection( data ) {
		// Allow fresh requests to be sent
		working = false;
		$post_selector.removeClass( 'updating' );

		// Alert the user if the operation was unsucessful
		if ( 'undefined' === typeof data.success || ! data.success ) {
			update_failed();
			return;
		}

		// Were further changes to the filter values made while this request was pending?
		// Make a further update if so
		if ( queued_update ) {
			queued_update = false;
			on_filter_change();
		}

		set_available_posts( 'object' === typeof data.data.posts ? data.data.posts : [] );
	}

	/**
	 * Inform the user if the post selection update failed for any reason.
	 */
	function update_failed() {
		alert( tribe_move_tickets_data.update_post_list_failure );
	}

	/**
	 * Given an array of post titles (indexed by their post ID), update the contents
	 * of the post selector box.
	 *
	 * @param post_list
	 */
	function set_available_posts( post_list ) {
		var count = 0;
		$post_selector.find( 'label, p' ).remove();

		for ( var key in post_list ) {
			count++;
			var id   = parseInt( key, 10 );
			var name = post_list[ key ];

			$post_selector.append(
				'<label> <input type="radio" name="post-choice" value="' + id + '"> ' + name + ' </label>'
			);
		}

		if ( ! count ) {
			$post_selector.append(
				'<p>' + tribe_move_tickets_data.nothing_found + '</p>'
			)
		}
	}

	/**
	 * If the user selects an item from the list of available posts,
	 * perform UI updates as needed.
	 */
	function on_post_selection() {
		clear_current_choice();

		var $checked_radio_btn = $post_selector.find( 'input:checked' );

		if ( $checked_radio_btn.length ) {
			$checked_radio_btn.parent( 'label' ).addClass( 'selected' );
			$move_btn.prop( 'disabled', false );
		}
	}

	function clear_current_choice() {
		$post_selector.find( '.selected' ).removeClass( 'selected' );
	}

	/**
	 * Executes the move ticket type request.
	 */
	function do_move() {
		// If filtering is in progress or submission has been disabled, then do nothing
		if ( working || $move_btn.prop( 'disabled' ) ) {
			return;
		}

		var $checked_radio_btn = $post_selector.find( 'input:checked' );

		// It would be unusual if we reached this point without a post having been selected
		// but let's protect ourselves in any case
		if ( ! $checked_radio_btn.length ) {
			return;
		}

		var request = {
			'action':      'move_ticket_type',
			'check':       tribe_move_tickets_data.check,
			'type_id':     tribe_move_tickets_data.ticket_type_id,
			'src_post_id': tribe_move_tickets_data.src_post_id,
			'destination': parseInt( $checked_radio_btn.val(), 10 )
		};

		// Disable all form elements and show the spinner
		working = true;
		$post_type.prop( 'disabled', true );
		$search_terms.prop( 'disabled', true );
		$move_btn.prop( 'disabled', true );
		$post_selector.addClass( 'updating' );
		$spinner.fadeIn( 'fast' );

		$.post( ajaxurl, request, operation_success, 'json' );
	}

	function operation_success( response ) {
		// If something went amiss and no message was returned, substiture a generic failure message
		if ( 'undefined' === typeof response.data || 'string' !== typeof response.data.message ) {
			var message = tribe_move_tickets_data.unexpected_failure;
		} else {
			var message = response.data.message;
		}

		$main.fadeOut( 'fast', function() {
			$main.html( message );
			$main.fadeIn( 'fast' );
		} );

		// Respect top window redirects if set
		if ( 'string' === typeof response.data.redirect_top ) {
			var delay = ( 'number' === typeof response.data.redirect_top_delay )
				? response.data.redirect_top_delay
				: 2000;

			setTimeout( function () {
				top.location = response.data.redirect_top;
			}, delay );
		}
	}

	$( document ).ready( init );
}( jQuery, tribe_move_ticket_type_dialog ) );