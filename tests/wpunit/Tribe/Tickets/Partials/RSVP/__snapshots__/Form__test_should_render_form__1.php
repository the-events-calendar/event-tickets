<?php return '
<!-- This div is where the AJAX returns the form. -->
<div class="tribe-block__rsvp__form">
	<form
	name="tribe-rsvp-form"
	data-product-id="33"
>
	<input type="hidden" name="product_id[]" value="33">
	<input type="hidden" name="attendee[order_status]" value="1">
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
	for="quantity_33"
>
	Quantity</label>
<input
	type="number"
	id="quantity_33"
	name="quantity_33"
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
';
