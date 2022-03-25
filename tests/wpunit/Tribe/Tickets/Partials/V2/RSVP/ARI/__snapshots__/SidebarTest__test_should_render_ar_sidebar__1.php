<?php return '<div class="tribe-tickets__rsvp-ar-sidebar">

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

		<label
	class="tribe-common-a11y-visual-hide"
	for="tribe-tickets__rsvp-ar-quantity-number--11"
>
	Quantity</label>
<input
	type="number"
	id="tribe-tickets__rsvp-ar-quantity-number--11"
	name="tribe_tickets[11][quantity]"
	class="tribe-common-h4"
	step="1"
	min="1"
	value="1"
	required
	max="5"
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
		aria-controls="tribe-tickets-rsvp-11-guest-1-tab"
		id="tribe-tickets-rsvp-11-guest-1"
			>
		<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>		<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Main Guest		</span>
	</button>
</li>
	<script
	class="tribe-tickets__rsvp-ar-guest-list-item-template"
	id="tmpl-tribe-tickets__rsvp-ar-guest-list-item-template-11"
	type="text/template"
>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button
			class="tribe-tickets__rsvp-ar-guest-list-item-button tribe-tickets__rsvp-ar-guest-list-item-button--inactive"
			type="button"
			data-guest-number="{{data.attendee_id + 1}}"
			role="tab"
			aria-selected="false"
			aria-controls="tribe-tickets-rsvp-11-guest-{{data.attendee_id + 1}}-tab"
			id="tribe-tickets-rsvp-11-guest-{{data.attendee_id + 1}}"
					>
			<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
								Guest {{data.attendee_id + 1}}			</span>
		</button>
	</li>
</script>

</ul>

</div>

';
