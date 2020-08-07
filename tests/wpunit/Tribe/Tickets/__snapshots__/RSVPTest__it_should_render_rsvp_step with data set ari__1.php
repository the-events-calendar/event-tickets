<?php return '<div  class="tribe-tickets-loader__dots tribe-common-c-loader tribe-common-a11y-hidden" >
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--first"></div>
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--second"></div>
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--third"></div>
</div>



	<form
	class="tribe-tickets__rsvp-ar tribe-common-g-row tribe-common-g-row--gutters"
	name="tribe-tickets-rsvp-form-ari"
	data-rsvp-id="TICKET_ID"
>
	<div class="tribe-tickets__rsvp-ar-sidebar-wrapper tribe-common-g-col">
		<div class="tribe-tickets__rsvp-ar-sidebar">

	<h3 class="tribe-common-h5">
	Attendee Registration</h3>

	<div class="tribe-tickets__rsvp-ar-quantity">
	<span class="tribe-common-h7 tribe-common-h--alt">
		Total Guests	</span>

	<div class="tribe-tickets__rsvp-ar-quantity-input">
		<button
	type="button"
	class="tribe-tickets__rsvp-ar-quantity-input-number tribe-tickets__rsvp-ar-quantity-input-number--minus"
>
	<span class="tribe-common-a11y-hidden">Minus</span>
</button>

		<input
	type="number"
	name="tribe_tickets[TICKET_ID][quantity]"
	class="tribe-common-h4"
	step="1"
	min="1"
	value="1"
	required
	max="10"
		autocomplete="off"
/>

		<button
	type="button"
	class="tribe-tickets__rsvp-ar-quantity-input-number tribe-tickets__rsvp-ar-quantity-input-number--plus"
>
	<span class="tribe-common-a11y-hidden">Plus</span>
</button>
	</div>

</div>

	<ul
	class="tribe-tickets__rsvp-ar-guest-list tribe-common-h6"
	role="tablist"
	aria-label="Guests"
>

	<li class="tribe-tickets__rsvp-ar-guest-list-item">
	<button
		class="tribe-tickets__rsvp-ar-guest-list-item-button"
		type="button"
		data-guest-number="1"
		role="tab"
		aria-selected="true"
		aria-controls="tribe-tickets-rsvp-337-guest-1-tab"
		id="tribe-tickets-rsvp-337-guest-1"
			>
		<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>		<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Main Guest		</span>
	</button>
</li>
	<script
	class="tribe-tickets__rsvp-ar-guest-list-item-template"
	id="tmpl-tribe-tickets__rsvp-ar-guest-list-item-template-337"
	type="text/template"
>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button
			class="tribe-tickets__rsvp-ar-guest-list-item-button tribe-tickets__rsvp-ar-guest-list-item-button--inactive"
			type="button"
			data-guest-number="{{data.attendee_id + 1}}"
			role="tab"
			aria-selected="false"
			aria-controls="tribe-tickets-rsvp-337-guest-{{data.attendee_id + 1}}-tab"
			id="tribe-tickets-rsvp-337-guest-{{data.attendee_id + 1}}"
					>
			<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
								Guest {{data.attendee_id + 1}}			</span>
		</button>
	</li>
</script>

</ul>

</div>

	</div>

	<div class="tribe-tickets__rsvp-ar-form-wrapper tribe-common-g-col">
		<div class="tribe-tickets__rsvp-ar-form">

	<input type="hidden" name="tribe_tickets[TICKET_ID][ticket_id]" value="TICKET_ID">
	<input type="hidden" name="tribe_tickets[TICKET_ID][attendees][0][order_status]" value="not-going">
	<input type="hidden" name="tribe_tickets[TICKET_ID][attendees][0][optout]" value="1">

	
<div
	class="tribe-tickets__rsvp-ar-form-guest"
	data-guest-number="1"
	tabindex="0"
	role="tabpanel"
	id="tribe-tickets-rsvp-337-guest-1-tab"
	aria-labelledby="tribe-tickets-rsvp-337-guest-1"
>
	<header>
	<h3 class="tribe-tickets__rsvp-ar-form-title tribe-common-h5">
		Main Guest	</h3>
</header>

	<div class="tribe-tickets__form">

	<div class="tribe-tickets__form-message tribe-tickets__form-message--error tribe-common-b3 tribe-common-a11y-hidden">
	<svg  class="tribe-tickets-svgicon tribe-tickets__form-message--error-icon"  xmlns="http://www.w3.org/2000/svg" width="18" height="18"><g fill="none" fill-rule="evenodd" transform="translate(1 1)"><circle cx="8" cy="8" r="7.467" stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><circle id="dot" cx="8" cy="11.733" r="1.067" fill="#141827" fill-rule="nonzero"/><path stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3.733v4.8"/></g></svg>	<span class="tribe-tickets__form-message-text">
		<p>Please fill in required information before proceeding</p>
	</span>
</div>

	<div class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-name-337"
	>
		Name<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="text"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-name"
		name="tribe_tickets[TICKET_ID][attendees][0][full_name]"
		id="tribe-tickets-rsvp-name-337"
		value=""
		required
		placeholder="Your Name"
	>
</div>

	<div class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-email-337"
	>
		Email<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="email"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-email"
		name="tribe_tickets[TICKET_ID][attendees][0][email]"
		id="tribe-tickets-rsvp-email-337"
		value=""
		required
		placeholder="your@email.com"
	>
</div>

	
</div>

	<div class="tribe-tickets__rsvp-form-buttons">
	<button
		class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel"
		type="reset"
	>
		Cancel	</button>

	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--next tribe-common-a11y-hidden"
		type="button"
			>
		Next guest	</button>

	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--submit"
		type="submit"
			>
		Finish	</button>
</div>

</div>

	<script
	class="tribe-tickets__rsvp-ar-form-guest-template"
	id="tmpl-tribe-tickets__rsvp-ar-form-guest-template-337"
	type="text/template"
>
	<div
		class="tribe-tickets__rsvp-ar-form-guest tribe-common-a11y-hidden"
		data-guest-number="{{data.attendee_id + 1}}"
		tabindex="0"
		role="tabpanel"
		id="tribe-tickets-rsvp-337-guest-{{data.attendee_id + 1}}-tab"
		aria-labelledby="tribe-tickets-rsvp-337-guest-{{data.attendee_id + 1}}"
		hidden
	>

		<header>
	<h3 class="tribe-tickets__rsvp-ar-form-title tribe-common-h5">
				Guest {{data.attendee_id + 1}}	</h3>
</header>

		<div class="tribe-tickets__form">

	<div class="tribe-tickets__form-message tribe-tickets__form-message--error tribe-common-b3 tribe-common-a11y-hidden">
	<svg  class="tribe-tickets-svgicon tribe-tickets__form-message--error-icon"  xmlns="http://www.w3.org/2000/svg" width="18" height="18"><g fill="none" fill-rule="evenodd" transform="translate(1 1)"><circle cx="8" cy="8" r="7.467" stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><circle id="dot" cx="8" cy="11.733" r="1.067" fill="#141827" fill-rule="nonzero"/><path stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3.733v4.8"/></g></svg>	<span class="tribe-tickets__form-message-text">
		<p>Please fill in required information before proceeding</p>
	</span>
</div>

	</div>

		<div class="tribe-tickets__rsvp-form-buttons">
	<button
		class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel"
		type="reset"
	>
		Cancel	</button>

	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--next tribe-common-a11y-hidden"
		type="button"
			>
		Next guest	</button>

	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--submit"
		type="submit"
			>
		Finish	</button>
</div>

	</div>
</script>

</div>
	</div>
</form>

';
