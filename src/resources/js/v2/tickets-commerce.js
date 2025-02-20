/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.1.9
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET Tickets Commerce Object in the Global Tribe variable
 *
 * @since 5.1.9
 * @type   {Object}
 */
tribe.tickets.commerce = {};

/**
 * Initializes in a Strict env the code that manages the plugin tickets commerce.
 *
 * @since 5.1.9
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.commerce
 * @return {void}
 */
( function( $, obj, tecTicketsCommerce ) {
	const $document = $( document );

	/**
	 * Ticket Commerce custom Events.
	 *
	 * @since 5.1.10
	 */
	obj.customEvents = {
		showLoader: 'showLoader.tecTicketsCommerce',
		hideLoader: 'hideLoader.tecTicketsCommerce',
	};

	/*
	 * Tickets Commerce Selectors.
	 *
	 * @since 5.1.9
	 */
	obj.selectors = {
		checkoutContainer: '.tribe-tickets__commerce-checkout',
		checkoutItem: '.tribe-tickets__commerce-checkout-cart-item',
		checkoutItemDescription: '.tribe-tickets__commerce-checkout-cart-item-details-description',
		checkoutItemDescriptionOpen: '.tribe-tickets__commerce-checkout-cart-item-details--open',
		checkoutItemDescriptionButtonMore: '.tribe-tickets__commerce-checkout-cart-item-details-button--more', // eslint-disable-line max-len
		checkoutItemDescriptionButtonLess: '.tribe-tickets__commerce-checkout-cart-item-details-button--less', // eslint-disable-line max-len
		hiddenElement: '.tribe-common-a11y-hidden',
		nonce: '#tec-tc-checkout-nonce',
		purchaserFormContainer: '.tribe-tickets__commerce-checkout-purchaser-info-wrapper',
		purchaserName: '.tribe-tickets__commerce-checkout-purchaser-info-form-field-name',
		purchaserEmail: '.tribe-tickets__commerce-checkout-purchaser-info-form-field-email',

		// Coupon related selectors.
		couponInputContainer: '.tec-tickets__commerce-checkout-cart-coupons',
		couponInput: '#coupon_input',
		couponError: '.tec-tickets__commerce-checkout-cart-coupons__error',
		couponApplyButton: '#coupon_apply',
		couponAppliedSection: '.tec-tickets__commerce-checkout-cart-coupons__applied',
		couponAppliedValue: '.tec-tickets__commerce-checkout-cart-coupons__applied-value',
		couponRemoveButton: '.tec-tickets__commerce-checkout-cart-coupons__remove-button',
	};

	/**
	 * Show the loader/spinner.
	 *
	 * @since 5.1.10
	 */
	obj.loaderShow = function() {
		tribe.tickets.loader.show( $( obj.selectors.checkoutContainer ) );
	};

	/**
	 * Hide the loader/spinner.
	 *
	 * @since 5.1.10
	 */
	obj.loaderHide = function() {
		tribe.tickets.loader.hide( $( obj.selectors.checkoutContainer ) );
	};

	/**
	 * Bind loader events.
	 *
	 * @since 5.1.10
	 */
	obj.bindLoaderEvents = function() {
		$document.on( obj.customEvents.showLoader, obj.loaderShow );
		$document.on( obj.customEvents.hideLoader, obj.loaderHide );
	};

	/**
	 * Toggle the checkout item description visibility.
	 *
	 * @since 5.1.9
	 * @param {event} event The event.
	 * @return {void}
	 */
	obj.checkoutItemDescriptionToggle = function( event ) {
		if ( 'keydown' === event.type && 13 !== event.keyCode ) {
			return;
		}

		const trigger = event.currentTarget;

		if ( ! trigger ) {
			return;
		}

		const $trigger = $( trigger );

		if (
			! $trigger.hasClass( obj.selectors.checkoutItemDescriptionButtonMore.className() ) &&
			! $trigger.hasClass( obj.selectors.checkoutItemDescriptionButtonLess.className() )
		) {
			return;
		}

		const $parent = $trigger.closest( obj.selectors.checkoutItem );
		const $target = $( '#' + $trigger.attr( 'aria-controls' ) );

		if ( ! $target.length || ! $parent.length ) {
			return;
		}

		// Let our CSS handle the hide/show. Also allows us to make it responsive.
		const onOff = ! $parent.hasClass( obj.selectors.checkoutItemDescriptionOpen.className() );
		$parent.toggleClass( obj.selectors.checkoutItemDescriptionOpen.className(), onOff );
		$target.toggleClass( obj.selectors.checkoutItemDescriptionOpen.className(), onOff );
		$target.toggleClass( obj.selectors.hiddenElement.className() );
	};

	/**
	 * Binds the checkout item description toggle.
	 *
	 * @since 5.1.9
	 * @param {jQuery} $container jQuery object of the tickets container.
	 * @return {void}
	 */
	obj.bindCheckoutItemDescriptionToggle = function( $container ) {
		const $descriptionToggleButtons = $container.find( obj.selectors.checkoutItemDescriptionButtonMore + ', ' + obj.selectors.checkoutItemDescriptionButtonLess ); // eslint-disable-line max-len

		$descriptionToggleButtons
			.on( 'keydown', obj.checkoutItemDescriptionToggle )
			.on( 'click', obj.checkoutItemDescriptionToggle );
	};

	/**
	 * Unbinds the description toggle.
	 *
	 * @since 5.1.9
	 * @param {jQuery} $container jQuery object of the tickets container.
	 * @return {void}
	 */
	obj.unbindCheckoutItemDescriptionToggle = function( $container ) {
		const $descriptionToggleButtons = $container.find( obj.selectors.checkoutItemDescriptionButtonMore + ', ' + obj.selectors.checkoutItemDescriptionButtonLess ); // eslint-disable-line max-len

		$descriptionToggleButtons.off();
	};

	/**
	 * Binds events for checkout container.
	 *
	 * @since 5.1.9
	 * @param {jQuery} $container jQuery object of object of the tickets container.
	 * @return {void}
	 */
	obj.bindCheckoutEvents = function( $container ) {
		$document.trigger( 'beforeSetup.tecTicketsCommerce', [ $container ] );

		// Bind coupon apply event.
		obj.bindCouponApply();

		// Bind coupon remove event.
		obj.bindCouponRemove();

		// Bind container based events.
		obj.bindCheckoutItemDescriptionToggle( $container );

		// Bind loader visibility.
		obj.bindLoaderEvents();

		$document.trigger( 'afterSetup.tecTicketsCommerce', [ $container ] );
	};

	/**
	 * Get purchaser data if available.
	 *
	 * @since 5.3.0
	 * @param {jQuery} $container Container for the purchaser info fields.
	 * @return {Object}
	 */
	obj.getPurchaserData = ( $container ) => {
		const purchaser = {};

		if ( ! $container.length ) {
			return purchaser;
		}

		purchaser.name = $container.find( obj.selectors.purchaserName ).val();
		purchaser.email = $container.find( obj.selectors.purchaserEmail ).val();

		return purchaser;
	};

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since 5.1.9
	 * @return {void}
	 */
	obj.ready = function() {
		const $checkoutContainer = $document.find( obj.selectors.checkoutContainer );
		// Bind events for each tickets commerce checkout block.
		$checkoutContainer.each( function( index, block ) {
			obj.bindCheckoutEvents( $( block ) );
		} );
	};

	/**
	 * Updates the total price displayed on the page.
	 *
	 * @since TBD
	 * @param {string} newAmount The new total amount to display.
	 */
	obj.updateTotalPrice = function( newAmount ) {
		const $totalPriceElement = $( '.tribe-tickets__commerce-checkout-cart-footer-total-wrap' );

		const parser = new DOMParser();
		const unescapedAmount = parser.parseFromString(
			`<!doctype html><body>${ newAmount }`,
			'text/html',
		).body.textContent;

		$totalPriceElement.text( unescapedAmount );
	};

	obj.updateCouponDiscount = function( discount ) {
		const $couponValueElement = $( obj.selectors.couponAppliedValue );

		// Use DOMParser to unescape the discount value
		const parser = new DOMParser();
		const unescapedDiscount = parser.parseFromString(
			`<!doctype html><body>${ discount }`,
			'text/html',
		).body.textContent;

		$couponValueElement.text( unescapedDiscount );
	};

	obj.bindCouponApply = function() {
		let ajaxInProgress = false;

		$document.on( 'click', obj.selectors.couponApplyButton, applyCoupon );
		$document.on( 'keydown', obj.selectors.couponInput, function( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				applyCoupon();
			}
		} );

		/**
		 * Function to apply the coupon and handle AJAX request.
		 *
		 * @since TBD
		 */
		function applyCoupon() {
			// Prevent multiple AJAX requests at once.
			if ( ajaxInProgress ) {
				return;
			}

			const couponValue = $( obj.selectors.couponInput ).val().trim();
			const nonce = $( obj.selectors.nonce ).val();
			const $errorMessage = $( obj.selectors.couponError );

			// Hide the error message initially.
			$errorMessage.hide();

			// Ensure the coupon is not empty.
			if ( ! couponValue ) {
				$errorMessage.text( tecTicketsCommerce.i18n.couponCodeEmpty ).show();
				return;
			}

			ajaxInProgress = true;
			obj.loaderShow();

			// Get the cart hash from the URL.
			const cartHash = window.location.search.match( /tec-tc-cookie=([^&]*)/ );

			const requestData = {
				coupon: couponValue,
				nonce: nonce,
				payment_intent_id: window.tecTicketsCommerceGatewayStripeCheckout.paymentIntentData.id,
				purchaser_data: obj.getPurchaserData( $( obj.selectors.purchaserFormContainer ) ),
				cart_hash: cartHash[ 1 ],
			};

			$.ajax( {
				url: `${ tecTicketsCommerce.restUrl }coupons/apply`,
				method: 'POST',
				data: requestData,
				success( response ) {
					if ( response.success ) {
						// Hide input and button, show applied coupon.
						$( obj.selectors.couponInputContainer ).hide();

						// Display coupon value and discount.
						obj.updateCouponDiscount( response.discount );
						obj.updateTotalPrice( response.cart_amount );
						$( obj.selectors.couponAppliedSection ).show();
					} else {
						$errorMessage.text( response.message || tecTicketsCommerce.i18n.invalidCoupon ).show();
						$( obj.selectors.couponInputContainer ).show();
					}
				},
				error() {
					$errorMessage.text( tecTicketsCommerce.i18n.couponApplyError ).show();
					$( obj.selectors.couponInputContainer ).show();
				},
				complete() {
					obj.loaderHide();
					ajaxInProgress = false;
				},
			} );
		}
	};

	/**
	 * Bind the remove coupon button.
	 *
	 * @since TBD
	 */
	obj.bindCouponRemove = function() {
		$document.on( 'click', obj.selectors.couponRemoveButton, function() {
			let ajaxInProgress = false;

			// Prevent multiple AJAX requests at once.
			if ( ajaxInProgress ) {
				return;
			}

			const couponValue = $( obj.selectors.couponInput ).val().trim();
			const nonce = $( obj.selectors.nonce ).val();
			const $errorMessage = $( obj.selectors.couponError );

			// Hide the error message initially.
			$errorMessage.hide();

			// Ensure the coupon is not empty.
			if ( ! couponValue ) {
				$errorMessage.text( tecTicketsCommerce.i18n.cantDetermineCoupon ).show();
				return;
			}

			ajaxInProgress = true;
			obj.loaderShow();

			const cartHash = window.location.search.match( /tec-tc-cookie=([^&]*)/ );

			const requestData = {
				nonce: nonce,
				coupon: couponValue,
				payment_intent_id: window.tecTicketsCommerceGatewayStripeCheckout.paymentIntentData.id,
				cart_hash: cartHash[ 1 ],
			};

			// Perform the AJAX request to remove the coupon.
			$.ajax( {
				url: `${ window.tecTicketsCommerce.restUrl }coupons/remove`,
				method: 'POST',
				data: requestData,
				beforeSend() {
					obj.loaderShow();
				},
				success( response ) {
					if ( response.success ) {
						// Show input and apply button again.
						$( obj.selectors.couponInputContainer ).show();
						$( obj.selectors.couponInput ).val( '' );

						// Hide the applied coupon section.
						$( obj.selectors.couponAppliedSection ).hide();
						obj.updateTotalPrice( response.cart_amount );
					} else {
						$errorMessage.text( response.message || tecTicketsCommerce.i18n.couponRemoveFail ).show();
					}
				},
				error() {
					$errorMessage.text( tecTicketsCommerce.i18n.couponRemoveError ).show();
				},
				complete() {
					obj.loaderHide();
					ajaxInProgress = false;
				},
			} );
		} );
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce, window.tecTicketsCommerce || {} );
