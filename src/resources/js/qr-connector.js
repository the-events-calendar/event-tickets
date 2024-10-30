/* global tribe, jQuery  */

tribe.tickets = tribe.tickets || {};
tribe.tickets.admin = tribe.tickets.admin || {};

( function( $ ) {
	const obj = tribe.tickets.admin.qrConnector = {};

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since 5.7.0
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		trigger: '[data-tickets-qr-connector]',
		message: '[data-tickets-qr-connector-message]',
		image: '[data-tickets-qr-connector-image]',
		input: '[data-tickets-qr-connector-input]',
	};

	/**
	 * Triggers when the document is ready.
	 *
	 * @since 5.7.0
	 *
	 * @return {void}
	 */
	obj.ready = () => {
		obj.bind();
	};

	/**
	 * Bind all the Connector Events happening on ready.
	 *
	 * @since 5.7.0
	 *
	 * @retun {void}
	 */
	obj.bind = () => {
		$( document ).on( 'click', obj.selectors.trigger, obj.onTriggerClick );
	};

	/**
	 * Trigger Click Event for the QR Connector.
	 *
	 * @since 5.7.0
	 *
	 * @return {void}
	 */
	obj.onTriggerClick = ( event ) => {
		event.preventDefault();
		const $trigger = $( event.currentTarget );
		const containerSelector = $trigger.data( 'tickets-qr-connector-container' );
		const $container = $trigger.parents( containerSelector ).eq( 0 );

		const confirmed = confirm( $container.find( obj.selectors.message ).text().trim() );
		if ( ! confirmed ) {
			return;
		}

		obj.doAjaxRequest( $trigger );
	};

	/**
	 * AJAX to Generate and Save QR Key
	 *
	 * @since 5.7.0
	 *
	 * @param {jQuery} $trigger The jQuery element that triggered the event.
	 *
	 * @return {void}
	 */
	obj.doAjaxRequest = ( $trigger ) => {
		const ajaxAction = $trigger.data( 'tickets-qr-connector-action' );
		const nonce = $trigger.data( 'tickets-qr-connector-nonce' );
		const containerSelector = $trigger.data( 'tickets-qr-connector-container' );
		const $container = $trigger.parents( containerSelector ).eq( 0 );

		const $message = $container.find( obj.selectors.message );
		const $image = $container.find( obj.selectors.image );
		const $input = $container.find( obj.selectors.input );

		const request = {
			action: ajaxAction,
			confirm: nonce,
		};

		$trigger.prop( 'disabled', true );
		$image.css( 'opacity', 0.1 );
		$input.val( '--------' );

		// Send our request
		$.post(
			ajaxurl,
			request,
			( results ) => {
				if ( results.success ) {
					$message.html( '<p class="optin-success">' + results.data.msg + '</p>' );
					$input.val( results.data.key );
					$image.attr( 'src', results.data.qr_src );
				} else {
					$message.html( '<p class="optin-fail">' + results.data + '</p>' );
				}
				$trigger.prop( 'disabled', false );
				$image.css( 'opacity', 1 );
			},
		);
	};

	$( obj.ready );
} )( jQuery );
