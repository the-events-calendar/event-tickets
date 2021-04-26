/* global tribe */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type {PlainObject}
 */
tribe.tickets = tribe.tickets || {};
tribe.tickets.admin = tribe.tickets.admin || {};

/**
 * Configures admin commerce settings Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type {PlainObject}
 */
tribe.tickets.admin.commerceSettings = {};

/**
 * Initializes in a Strict env the code that manages the Tickets Commerce settings page.
 *
 * @since TBD
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} _   Underscore.js
 * @param  {PlainObject} obj tribe.tickets.admin.commerceSettings
 *
 * @return {void}
 */
( function( $, _, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * Selectors used for configuration and setup
	 *
	 * @since TBD
	 *
	 * @type {PlainObject}
	 */
	obj.selectors = {
		connectButton: '#js-give-paypal-on-boarding-handler',
		connectButtonWrap: '.connect-button-wrap',
		connectionSettingContainer: '#give-paypal-commerce-account-manager-field-wrap .connection-setting',
		container: '#tribe-field-tickets-commerce-paypal-commerce-configure',
		countrySelect: '#paypal_commerce_account_country',
		errorMessageTemplate: '.paypal-message-template',
		disconnectionSettingContainer: '#give-paypal-commerce-account-manager-field-wrap .disconnection-setting',
		disconnectPayPalAccountButton: '#js-give-paypal-disconnect-paypal-account',
		troubleNotice: '#give-paypal-onboarding-trouble-notice',
	};

	obj.observePayPalModal = function() {
		const paypalModalObserver = new MutationObserver( function( mutationsRecord ) {
			mutationsRecord.forEach( function( record ) {
				record.removedNodes.forEach( function( node ) {
					if ( 'PPMiniWin' !== node.getAttribute( 'id' ) ) {
						return;
					}

					obj.paypalErrorQuickHelp[0] && obj.paypalErrorQuickHelp.removeClass( 'tribe-common-a11y-hidden' );
				} );
			} );
		} );

		paypalModalObserver.observe( document.querySelector( 'body' ), {
			attributes: true,
			childList: true,
		} );
	}

	obj.maybeShowPCINotice = function() {
		if ( ! window.location.search.match( /paypal-commerce-account-connected=1/i ) ) {
			return;
		}

		// @todo Replace the i18n text here.
		const pciWarnings = [
				'Instruction text 1 here',
				'Instruction text 2 here',
			]
			.map( instruction => `<li>${ instruction }</li>` )
			.join( '' );

		// @todo Replace this logic.
		const isLive = false;

		// @todo Replace the i18n text here.
		const liveWarning = isLive ?
			`<p class="give-modal__description__warning">Live warning text here</p>` :
			'';

		// @todo Replace this modal.
		new Give.modal.GiveSuccessAlert( {
			classes: {
				modalWrapper: 'paypal-commerce-connect',
				cancelBtn: 'give-button--primary',
			},
			modalContent: {
				// @todo Replace the i18n text here.
				title: 'Connect success title here',
				// @todo Replace the i18n text here.
				body: `
					<div class="give-modal__description">
						${ liveWarning }
						<p>PCI Warning Text Here</p>
						<ul>${ pciWarnings }</ul>
					</div>
				`.trim(),
				// @todo Replace the i18n text here.
				cancelBtnTitle: 'Confirm text here',
			},
			closeOnBgClick: true,
		} ).render();
	};

	obj.setupPartnerLink = function( partnerLink ) {
		const payPalLink = document.querySelector( '[data-paypal-button]' );

		payPalLink.href = `${ partnerLink }&displayMode=minibrowser`;
		payPalLink.click();

		// This object will check if a class added to body or not.
		// If class added that means modal opened.
		// If class removed that means modal closed.
		obj.observePayPalModal();
	}

	/**
	 * Performs an AJAX request to get the partner URL.
	 *
	 * @since TBD
	 *
	 * @param {String} countryCode The country code.
	 *
	 * @return {void}
	 */
	obj.requestPartnerUrl = function( countryCode ) {
		// @todo Add AJAX handler for this.
		fetch( ajaxurl + `?action=tribe_tickets_paypal_commerce_get_partner_url&countryCode=${ countryCode }` )
			.then( response => response.json() )
			.then( function( res ) {
				// Handle success.
				if ( true === res.success ) {
					obj.setupPartnerLink( res.data.partnerLink );
				}

				obj.buttonState.enable();
			} )
			.then( function() {
				// Handle the error notice.
				// @todo Add AJAX handler for this.
				fetch( ajaxurl + '?action=tribe_tickets_paypal_commerce_onboarding_trouble_notice' )
					.then( response => response.json() )
					.then( function( res ) {
						if ( true !== res.success ) {
							return;
						}

						function createElementFromHTML( htmlString ) {
							const div = document.createElement( 'div' );
							div.innerHTML = htmlString.trim();
							return div.firstChild;
						}

						const buttonContainer = document.querySelector( obj.selectors.connectButtonWrap );
						paypalErrorQuickHelp && paypalErrorQuickHelp.remove();
						buttonContainer.append( createElementFromHTML( res.data ) );
					} );
			} );
	};

	obj.removeErrors = function() {
		const errorsContainer = document.querySelector( obj.selectors.errorMessageTemplate );

		if ( errorsContainer ) {
			errorsContainer.parentElement.remove();
		}
	}

	obj.handleConnectClick = function( evt ) {
		evt.preventDefault();
		obj.removeErrors();

		const countryCode = $( obj.selectors.countrySelect ).val();

		obj.buttonState = {
			enable: () => {
				obj.onBoardingButton.attr( 'disabled', false );
				evt.target.innerText = obj.onBoardingButton.data( 'initial-label' );
			},
			disable: () => {
				// Preserve initial label.
				if ( ! obj.onBoardingButton.data( 'initial-label' ) ) {
					obj.onBoardingButton.data( 'initial-label', obj.onBoardingButton.innerText );
				}

				obj.onBoardingButton.attr( 'disabled', true );

				// @todo Replace the i18n text here.
				evt.target.innerText = 'Processing text here';
			},
		};

		obj.buttonState.disable();

		// Hide paypal quick help message.
		obj.paypalErrorQuickHelp[0] && obj.paypalErrorQuickHelp.addClass( 'tribe-common-a11y-hidden' );
	};

	/**
	 * Handles the initialization of the gateway settings when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		obj.onBoardingButton = $( obj.selectors.onBoardingButton );
		obj.paypalErrorQuickHelp = $( obj.selectors.troubleNotice );

		if ( obj.onBoardingButton[0] ) {
			onBoardingButton.on( 'click', obj.handleConnectClick );
		}

		obj.maybeShowPCINotice();
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, window.underscore || window._, tribe.tickets.admin.commerceSettings );