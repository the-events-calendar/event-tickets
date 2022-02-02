<?php return '<div  class="tribe-common event-tickets tribe-tickets__tickets-wrapper" >
	<form
		id="tribe-tickets__tickets-form"
		action=""
		class="tribe-tickets__tickets-form tribe-tickets__form"
		method="post"
		enctype=\'multipart/form-data\'
		data-provider="Tribe__Tickets__Commerce__PayPal__Main"
		autocomplete="off"
		data-provider-id="tribe-commerce"
		data-post-id="[EVENT_ID]"
		novalidate
	>

		<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
		<input type="hidden" name="tribe_tickets_ar" value="1"/>
		<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_block_ar_data"/>

		<input
	type="hidden"
	id="add"
	name="add"
	value="1"
/>
<input name="provider" value="Tribe__Tickets__Commerce__PayPal__Main" class="tribe-tickets-provider" type="hidden">

		<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__tickets-title">
	Tickets</h2>

		<div id="tribe-tickets__notice__tickets-in-cart"  class="tribe-tickets__notice tribe-tickets__notice--barred tribe-tickets__notice--barred-left" >
	
	<div  class="tribe-common-b2 tribe-tickets-notice__content tribe-common-b3" >
		The numbers below include tickets for this event already in your cart. Clicking "Get Tickets" will allow you to edit any existing attendee information as well as change ticket quantities.	</div>
</div>

		<div
	id="tribe-modal-tickets-item-[TICKET_ID_1]"
	 class="tribe-tickets__tickets-item post-[TICKET_ID_1] tribe_tpp_tickets type-tribe_tpp_tickets status-publish hentry" 	 data-ticket-id="[TICKET_ID_1]" data-available="false" data-has-shared-cap="false" data-ticket-price="99" >

	<div  class="tribe-common-h7 tribe-common-h6--min-medium tribe-tickets__tickets-item-content-title"  >
		Test ticket for [EVENT_ID]</div>

<div class="tribe-tickets__tickets-item-details-summary">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--more"
		aria-controls="tribe__details__content__modal--[TICKET_ID_1]"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">Open the ticket description.</span>
		More	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--less"
		aria-controls="tribe__details__content__modal--[TICKET_ID_1]"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">Close the ticket description.</span>
		Less	</button>
</div>

<div
	id="tribe__details__content__modal--[TICKET_ID_1]"
	 class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__tickets-item-details-content tribe-common-a11y-hidden" >
	Test ticket description for [EVENT_ID]</div>
<div  class="tribe-tickets__tickets-item-extra" >

	<div  class="tribe-common-b2 tribe-common-b1--min-medium tribe-tickets__tickets-item-extra-price" >
		<span class="tribe-tickets__tickets-sale-price">
		
				<span class="tribe-formatted-currency-wrap tribe-currency-prefix">
					<span class="tribe-currency-symbol">$</span>
					<span class="tribe-amount">99.00</span>
				</span>
						</span>
</div>

	
<div class="tribe-common-b3 tribe-tickets__tickets-item-extra-available">

	
	<span class="tribe-tickets__tickets-item-extra-available-quantity"> 100 </span> available
</div>

	
</div>

	<div  class="tribe-common-h4 tribe-tickets__tickets-item-quantity" >
			<div class="tribe-common-b2 tribe-common-b2--bold tribe-tickets__tickets-item-quantity-unavailable">
	Sold Out</div>
	</div>

	
	
</div>
<div
	id="tribe-modal-tickets-item-[TICKET_ID_2]"
	 class="tribe-tickets__tickets-item post-[TICKET_ID_2] tribe_tpp_tickets type-tribe_tpp_tickets status-publish hentry" 	 data-ticket-id="[TICKET_ID_2]" data-available="false" data-has-shared-cap="false" data-ticket-price="99" >

	<div  class="tribe-common-h7 tribe-common-h6--min-medium tribe-tickets__tickets-item-content-title"  >
		Test ticket for [EVENT_ID]</div>

<div class="tribe-tickets__tickets-item-details-summary">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--more"
		aria-controls="tribe__details__content__modal--[TICKET_ID_2]"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">Open the ticket description.</span>
		More	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--less"
		aria-controls="tribe__details__content__modal--[TICKET_ID_2]"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide">Close the ticket description.</span>
		Less	</button>
</div>

<div
	id="tribe__details__content__modal--[TICKET_ID_2]"
	 class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__tickets-item-details-content tribe-common-a11y-hidden" >
	Test ticket description for [EVENT_ID]</div>
<div  class="tribe-tickets__tickets-item-extra" >

	<div  class="tribe-common-b2 tribe-common-b1--min-medium tribe-tickets__tickets-item-extra-price" >
		<span class="tribe-tickets__tickets-sale-price">
		
				<span class="tribe-formatted-currency-wrap tribe-currency-prefix">
					<span class="tribe-currency-symbol">$</span>
					<span class="tribe-amount">99.00</span>
				</span>
						</span>
</div>

	
<div class="tribe-common-b3 tribe-tickets__tickets-item-extra-available">

	
	<span class="tribe-tickets__tickets-item-extra-available-quantity"> 100 </span> available
</div>

	
</div>

	<div  class="tribe-common-h4 tribe-tickets__tickets-item-quantity" >
			<div class="tribe-common-b2 tribe-common-b2--bold tribe-tickets__tickets-item-quantity-unavailable">
	Sold Out</div>
	</div>

	
	
</div>

		<div class="tribe-tickets__tickets-footer">

	
	<div class="tribe-common-b2 tribe-tickets__tickets-footer-quantity">
	<span class="tribe-tickets__tickets-footer-quantity-label">
		Quantity:	</span>
	<span class="tribe-tickets__tickets-footer-quantity-number">0</span>
</div>

	<div class="tribe-common-b2 tribe-tickets__tickets-footer-total">
	<span class="tribe-tickets__tickets-footer-total-label">
		Total:	</span>
	<span class="tribe-tickets__tickets-footer-total-wrap">
		
				<span class="tribe-formatted-currency-wrap tribe-currency-prefix">
					<span class="tribe-currency-symbol">$</span>
					<span class="tribe-amount">0.00</span>
				</span>
				</span>
</div>

	
</div>

		
		<div  class="tribe-tickets-loader__dots tribe-common-c-loader tribe-common-a11y-hidden tribe-common-c-loader__dot tribe-common-c-loader__dot--third" >
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--first"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--second"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
	<svg  class="tribe-common-c-svgicon tribe-common-c-svgicon--dot tribe-common-c-loader__dot tribe-common-c-loader__dot--third"  viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"/></svg>
</div>

	</form>

	</div>
';
