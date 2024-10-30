/* global ClipboardJS */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 4.8.14
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.2.0
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe = tribe.tickets.commerce.gateway.stripe || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.3.0
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe.webhooks = {};

( ( $, obj, ajaxurl ) => {
	/**
	 * Stores the all selectors used on this module.
	 *
	 * @since 5.3.0
	 * @type {Object}
	 */
	obj.selectors = {
		button: '.tribe-field-tickets-commerce-stripe-webhooks-copy',
		signingKey: '[name="tickets-commerce-stripe-webhooks-signing-key"]',
		statusLabel: '.tribe-field-tickets-commerce-stripe-webhooks-signing-key-status',
		tooltip: '.tooltip',
		genericDashicon: '.dashicons',
		saveButton: 'input#tribeSaveSettings',
	};

	/**
	 * Stores the ClipboardJS instance for later reference.
	 *
	 * @since 5.3.0
	 * @type {Object}
	 */
	obj.clipboardButton = null;

	/**
	 * Configures the Copy URL UI.
	 *
	 * @since 5.3.0
	 */
	obj.setupCopyUrl = () => {
		obj.clipboardButton = new ClipboardJS( obj.selectors.button );
		$( obj.selectors.button ).on( 'click', event => event.preventDefault() );
	};

	/**
	 * Configures the signing key input events.
	 *
	 * @since 5.3.0
	 */
	obj.setupSigningValidation = () => {
		$( obj.selectors.signingKey ).on( 'change', obj.onSigningFieldChange );
	};

	/**
	 * Initiate the process of validating a signing key
	 *
	 * @since 5.5.6
	 * @param $field the key element
	 * @param $icon  the icon element
	 * @param $label the label element
	 * @returns {Promise<*>} result of the validation request
	 */
	obj.initiateValidation = async ( $field, $icon, $label ) => {
		const params = new URLSearchParams();
		params.set( 'signing_key', $field.val() );
		params.set( 'action', $field.data( 'ajaxAction' ) );
		params.set( 'tc_nonce', $field.data( 'ajaxNonce' ) );

		const args = {
			timeout: 30000,
			body: params,
			hooks: {
				beforeRequest: [
					() => {
						$label.text( $field.data( 'loadingText' ) );
						$icon.removeClass( [ 'dashicons-no', 'dashicons-yes' ] )
							.addClass( 'dashicons-update' );
					},
				],
			},
		};

		return await tribe.ky.post( ajaxurl, args ).json();
	};

	/**
	 * Check if current key has been verified
	 *
	 * @since 5.5.6
	 * @param $field the key element
	 * @param $icon  the icon element
	 * @param $label the label element
	 * @returns {Promise<*>} result of the verification request
	 */
	obj.checkValidationSuccess = async ( $field, $icon, $label ) => {
		const params = new URLSearchParams();
		params.set( 'signing_key', $field.val() );
		params.set( 'action', $field.data( 'ajaxActionVerify' ) );
		params.set( 'tc_nonce', $field.data( 'ajaxNonce' ) );

		const args = {
			timeout: 30000,
			body: params,
			hooks: {
				beforeRequest: [
					() => {
						$label.text( $field.data( 'loadingText' ) );
						$icon.removeClass( [ 'dashicons-no', 'dashicons-yes' ] )
							.addClass( 'dashicons-update' );
					},
				],
			},
		};

		return await tribe.ky.post( ajaxurl, args ).json();
	};

	/**
	 * When the signing field changes.
	 *
	 * @since 5.3.0
	 * @param event {Event}
	 * @return {Promise<*>}
	 */
	// eslint-disable-next-line
	obj.onSigningFieldChange = async ( event ) => {
		const $field = $( event.target );
		const $tooltip = $field.siblings( obj.selectors.tooltip );
		const $statusIcon = $tooltip.find( obj.selectors.genericDashicon );
		const $statusLabel = $tooltip.find( obj.selectors.statusLabel );
		const $saveButton = $( obj.selectors.saveButton );

		// Do not make any attempts when empty.
		if ( $field.val().trim() === '' ) {
			return;
		}

		$field.prop( 'disabled', true );
		$saveButton.prop( 'disabled', true );

		let response = await obj.initiateValidation( $field, $statusIcon, $statusLabel );

		if ( response.data.is_valid_webhook ) {
			// We were able to validate the key in the first request
			$statusIcon.removeClass( [ 'dashicons-update' ] ).addClass( 'dashicons-yes' );
		} else {
			// Make a second request to check for success.
			response = await obj.checkValidationSuccess( $field, $statusIcon, $statusLabel );

			if ( response.data.is_valid_webhook ) {
				$statusIcon.removeClass( [ 'dashicons-update' ] ).addClass( 'dashicons-yes' );
			} else {
				$statusIcon.removeClass( [ 'dashicons-update' ] ).addClass( 'dashicons-no' );
			}
		}

		$statusLabel.text( response.data.status );
		$field.prop( 'disabled', false );
		$saveButton.prop( 'disabled', false );

		return response;
	};

	/**
	 * Runs when jQuery determines that the document is ready.
	 */
	obj.ready = () => {
		obj.setupCopyUrl();
		obj.setupSigningValidation();
	};

	$( document ).ready( obj.ready );
} )( jQuery, tribe.tickets.commerce.gateway.stripe.webhooks, window.ajaxurl );
