<?php return '<div
	id="tribe-block-tickets-item-{{TICKET_ID}}"
	 class="tribe-tickets__tickets-item post-{{TICKET_ID}} tribe_tpp_tickets type-tribe_tpp_tickets status-publish hentry" 	 data-ticket-id="{{TICKET_ID}}" data-available="false" data-has-shared-cap="false" data-ticket-price="99" data-whatever="value" >

	<div  class="tribe-tickets__tickets-item-content-title-container" >
		<div  class="tribe-common-h7 tribe-common-h6--min-medium tribe-tickets__tickets-item-content-title" >
				Test ticket for {{POST_ID}}	</div>
</div>


<div
	id="tribe__details__content--{{TICKET_ID}}"
	 class="tribe-common-b2 tribe-common-b3--min-medium tribe-tickets__tickets-item-details-content" >
	Test ticket description for {{POST_ID}}</div>
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

	
		<input
		name="attendee[optout]"
		value="1"
		type="hidden"
	/>
	
</div>
';
