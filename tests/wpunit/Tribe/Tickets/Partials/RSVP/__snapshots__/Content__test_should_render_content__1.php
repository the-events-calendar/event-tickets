<?php return '<div class="tribe-block__rsvp__content">

	<div class="tribe-block__rsvp__details__status">
		<div class="tribe-block__rsvp__details">

	<header class="tribe-block__rsvp__title">
	Test RSVP ticket for 8</header>

	<div class="tribe-block__rsvp__description">
	<p>Ticket RSVP ticket excerpt for 8</p>
</div>

	<div class="tribe-block__rsvp__availability">
			<span class="tribe-block__rsvp__quantity">100 </span>
		remaining	</div>

</div>
		<div class="tribe-block__rsvp__status">
	
		<span>
	<button
	class="tribe-block__rsvp__status-button tribe-block__rsvp__status-button--going tribe-active"
	 disabled=\'disabled\'	>
		<span>Going</span>
		
	</button>
</span>
		
	</div>
	</div>

	
<!-- This div is where the AJAX returns the form. -->
<div class="tribe-block__rsvp__form">
	<form
	name="tribe-rsvp-form"
	data-product-id="5"
>
	<input type="hidden" name="product_id[]" value="5">
	<input type="hidden" name="attendee[order_status]" value="yes">
	<!-- Maybe add nonce over here? Try to leave templates as clean as possible -->

	<div class="tribe-left">
					<div class="tribe-block__rsvp__number-input">
	<div class="tribe-block__rsvp__number-input-inner">
		<button
	type="button"
	class="tribe-block__rsvp__number-input-button tribe-block__rsvp__number-input-button--minus"
></button>

		<label
	class="screen-reader-text"
	for="quantity_5"
>
	Quantity</label>
<input
	type="number"
	id="quantity_5"
	name="quantity_5"
	class="tribe-tickets-quantity"
	step="1"
	min="1"
	value="1"
	required
	max="100"
	/>

		<button
	type="button"
	class="tribe-block__rsvp__number-input-button tribe-block__rsvp__number-input-button--plus"
></button>
	</div>
	<span class="tribe-block__rsvp__number-input-label">
		RSVPs	</span>
</div>			</div>

	<div class="tribe-right">
		<div class="tribe-block__rsvp__message__error">

	Please fill in the RSVP confirmation name and email fields.
</div>
					<input
	type="text"
	name="attendee[full_name]"
	class="tribe-tickets-full-name"
	placeholder="Full Name"
	value=""
	required
/>
<input
	type="email"
	name="attendee[email]"
	class="tribe-tickets-email"
	placeholder="Email"
	value=""
	required
/>
	<input name="attendee[optout]" value="1" type="hidden" />
							<button
	type="submit"
	name="tickets_process"
	value="1"
	class="tribe-block__rsvp__submit-button"
>
	Submit RSVP</button>			</div>
</form>
</div>

</div>
';
