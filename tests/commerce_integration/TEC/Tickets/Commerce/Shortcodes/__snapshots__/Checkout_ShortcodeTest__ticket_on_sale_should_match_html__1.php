<?php return '<link rel=\'stylesheet\' id=\'tec-variables-skeleton-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/common/build//css/variables-skeleton.css?ver={{COMMON_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-common-skeleton-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/common/build//css/common-skeleton.css?ver={{COMMON_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tec-variables-full-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/common/build//css/variables-full.css?ver={{COMMON_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-common-full-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/common/build//css/common-full.css?ver={{COMMON_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-tickets-commerce-stripe-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/src/resources/css/tickets-commerce/gateway/stripe.css?ver={{ET_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-tickets-commerce-paypal-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/src/resources/css/tickets-commerce/gateway/paypal.css?ver={{ET_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-common-responsive-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/src/resources/css/common-responsive.css?ver={{ET_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-tickets-commerce-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/src/resources/css/tickets-commerce.css?ver={{ET_VERSION}}\' type=\'text/css\' media=\'all\' />
<link rel=\'stylesheet\' id=\'tribe-tickets-commerce-free-style-css\' href=\'http://wordpress.test/wp-content/plugins/event-tickets/src/resources/css/tickets-commerce/gateway/free.css?ver={{ET_VERSION}}\' type=\'text/css\' media=\'all\' />
<div class="tribe-common event-tickets">
	<section
		class="tribe-tickets__commerce-checkout"
		 data-js="tec-tickets-commerce-notice" data-notice-default-title="Checkout Unavailable!" data-notice-default-content="Checkout is not available at this time because a payment method has not been set up for this event. Please notify the site administrator." 	>
		<input type="hidden" id="tec-tc-checkout-nonce" name="tec-tc-checkout-nonce" value="jhd73jd873" /><input type="hidden" name="_wp_http_referer" value="" />		<header class="tribe-tickets__commerce-checkout-header">
	<h3 class="tribe-common-h2 tribe-tickets__commerce-checkout-header-title">
	Purchase Tickets</h3>
	<div class="tribe-common-b2 tribe-tickets__commerce-checkout-header-links">
	
<a
	class="tribe-common-anchor-alt tribe-tickets__commerce-checkout-header-link-back-to-event"
	href="http://wordpress.test/page-with-tickets"
>back to event</a>
</div>
</header>
					
<div class="tribe-tickets__commerce-checkout-cart">

	<header class="tribe-tickets__commerce-checkout-cart-header">
	<h4 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__commerce-checkout-cart-header-title">
		<a href="http://wordpress.test/page-with-tickets">
			Page with Tickets		</a>
	</h4>
</header>

	<div class="tribe-tickets__commerce-checkout-cart-items">
	<article
	 class="tribe-tickets__commerce-checkout-cart-item post-{{ticket_id2}} tec_tc_ticket type-tec_tc_ticket status-publish hentry tribe-common-b1" 	 data-ticket-id="{{ticket_id2}}" data-ticket-quantity="1" data-ticket-price="10.00" >

	<div class="tribe-tickets__commerce-checkout-cart-item-details">

	<div class="tribe-common-h6 tribe-tickets__commerce-checkout-cart-item-details-title">
	Test TC ticket for {{page_id}}</div>

	<div class="tribe-tickets__commerce-checkout-cart-item-details-toggle">
	<button
		type="button"
		class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__commerce-checkout-cart-item-details-button--more"
		aria-controls="tribe-tickets__commerce-checkout-cart-item-details-description--{{ticket_id2}}"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">
			Open the ticket description in checkout.		</span>
		<span class="tribe-tickets__commerce-checkout-cart-item-details-button-text">
			More info		</span>
	</button>
	<button
		type="button"
		class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__commerce-checkout-cart-item-details-button--less"
		aria-controls="tribe-tickets__commerce-checkout-cart-item-details-description--{{ticket_id2}}"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">
			Close the ticket description in checkout.		</span>
		<span class="tribe-tickets__commerce-checkout-cart-item-details-button-text">
			Less info		</span>
	</button>
</div>

	<div id="tribe-tickets__commerce-checkout-cart-item-details-description--{{ticket_id2}}"  class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__commerce-checkout-cart-item-details-description tribe-common-a11y-hidden" >
	Test TC ticket description for {{page_id}}
	</div>

</div>

	<div class="tribe-tickets__commerce-checkout-cart-item-price">
	
<span class="tec-tickets-price amount">
	<ins>
	<span class="tec-tickets-price__sale-price amount">
		<bdi>
			&#x24;10.00		</bdi>
	</span>
</ins>
<del aria-hidden="true">
	<span class="tec-tickets-price__regular-price amount">
		<bdi>
			&#x24;20.00		</bdi>
	</span>
</del></span>
</div>

	<div class="tribe-tickets__commerce-checkout-cart-item-quantity">
	1</div>

	<div class="tribe-tickets__commerce-checkout-cart-item-subtotal">
	&#x24;0.00</div>

</article>
</div>

	
<footer  class="tribe-tickets__commerce-checkout-cart-footer tribe-common-b1" >
	<div class="tribe-common-b2 tribe-tickets__form tec-tickets-commerce-checkout-cart__coupons">
	<button  class="tec-tickets-commerce-checkout-cart__coupons-add-link" >
		Add coupon code	</button>
	<div  class="tec-tickets-commerce-checkout-cart__coupons-input-container tribe-common-a11y-hidden" >
		<input
			class="tec-tickets-commerce-checkout-cart__coupons-input-field"
			type="text"
			id="tec-tickets-commerce-checkout-cart__coupon-input-field"
			name="coupons"
			aria-describedby="tec-tickets-commerce-checkout-cart__coupons-error-text"
			aria-label="Enter coupon code"
			placeholder="Enter coupon code"
			value=""
		/>
		<button  class="tribe-common-c-btn-border tec-tickets-commerce-checkout-cart__coupons-apply-button" >
			Apply		</button>
	</div>
	<p
		id="tec-tickets-commerce-checkout-cart__coupons-error-text"
		class="tec-tickets-commerce-checkout-cart__coupons-input-error tribe-common-a11y-hidden"
		aria-live="polite"
		role="alert"
	>
		Invalid coupon code	</p>
	<div  class="tec-tickets-commerce-checkout-cart__coupons-applied-container tribe-common-a11y-hidden" >
		<ul>
			<li>
				<span class="tribe-tickets__commerce-checkout-cart-footer-quantity-label tec-tickets-commerce-checkout-cart__coupons-applied-text">
					<span class="tec-tickets-commerce-checkout-cart__coupons-applied-label">
											</span>
					<button class="tec-tickets-commerce-checkout-cart__coupons-remove-button" type="button">
						<img
							src="http://wordpress.test/wp-content/plugins/event-tickets/common/src/resources/images/icons/close.svg"
							alt="Icon to remove coupon"
							title="Remove coupon"
						>
					</button>
				</span>
				<span class="tribe-tickets__commerce-checkout-cart-footer-quantity-number tec-tickets-commerce-checkout-cart__coupons-discount-amount">
									</span>
			</li>
		</ul>
	</div>
</div>
<div class="tribe-tickets__commerce-checkout-cart-footer-quantity">
	<span class="tribe-tickets__commerce-checkout-cart-footer-quantity-label">Quantity: </span><span class="tribe-tickets__commerce-checkout-cart-footer-quantity-number">1</span></div>
<div class="tribe-tickets__commerce-checkout-cart-footer-total">
	<span class="tribe-tickets__commerce-checkout-cart-footer-total-label">Total: </span><span class="tribe-tickets__commerce-checkout-cart-footer-total-wrap">&#x24;0.00</span></div>
</footer>

</div>
				<div  class="tribe-tickets-loader__dots tribe-common-c-loader tribe-common-a11y-hidden tec-tickets__admin-settings-tickets-commerce-gateway-connected-resync-button-icon" >
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--first"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--second"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--third"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
</div>
		<div id="tribe-tickets__commerce-checkout-footer-notice-error--no-gateway"  class="tribe-tickets__notice tribe-tickets__notice--error tribe-tickets__commerce-checkout-notice" >
			<h3 class="tribe-common-h7 tribe-tickets-notice__title">Checkout Error!</h3>
	
	<div  class="tribe-common-b2 tribe-tickets-notice__content tribe-tickets__commerce-checkout-notice-content" >
		Something went wrong!	</div>
</div>
				<div class="tribe-tickets__form tribe-tickets__commerce-checkout-purchaser-info-wrapper tribe-common-b2">
	<h4 class="tribe-common-h5 tribe-tickets__commerce-checkout-purchaser-info-title">Purchaser info</h4>
	<form class="tribe-tickets__commerce-checkout-purchaser-info-wrapper__form">
		<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--text">
	<label for="tec-tc-purchaser-name"  class="tribe-tickets__form-field-label tribe-tickets__commerce-checkout-purchaser-info-name-field-label" >
		Person purchasing tickets:	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-name"
			name="purchaser-name"
			autocomplete="off"
			placeholder="First and last name"
			 class="tribe-tickets__commerce-checkout-purchaser-info-form-field tribe-tickets__commerce-checkout-purchaser-info-form-field-name tribe-common-form-control-text__input tribe-tickets__form-field-input" 			required
					/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			Your first and last names are required		</div>
	</div>
</div>
		<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--email">
	<label for="tec-tc-purchaser-email"  class="tribe-tickets__form-field-label tribe-tickets__commerce-checkout-purchaser-info-email-field-label" >
		Email address	</label>

	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="email"
			id="tec-tc-purchaser-email"
			name="purchaser-email"
			autocomplete="off"
			 class="tribe-common-b2 tribe-tickets__commerce-checkout-purchaser-info-form-field tribe-tickets__commerce-checkout-purchaser-info-form-field-email tribe-common-form-control-text__input tribe-tickets__form-field-input" 			required
					/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			Your email address is required		</div>
		<div class="tribe-common-b3 tribe-tickets__form-field-description">
			Your tickets will be sent to this email address		</div>
	</div>
</div>
			</form>
</div>
				<footer class="tribe-tickets__commerce-checkout-footer">
	<div id="tribe-tickets__commerce-checkout-footer-notice-error--no-gateway"  class="tribe-tickets__notice tribe-tickets__notice--error tribe-tickets__commerce-checkout-footer-notice-error--no-gateway" >
			<h3 class="tribe-common-h7 tribe-tickets-notice__title">Checkout Unavailable!</h3>
	
	<div  class="tribe-common-b2 tribe-tickets-notice__content tribe-tickets__commerce-checkout-notice-content" >
		Checkout is not available at this time because a payment method has not been set up. Please notify the site administrator.	</div>
</div>
</footer>
			</section>
</div>
';
