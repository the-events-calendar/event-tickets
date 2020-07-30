<?php return '<ul class="tribe-tickets__rsvp-ar-guest-list tribe-common-h6">

	<li class="tribe-tickets__rsvp-ar-guest-list-item">
	<button
		class="tribe-tickets__rsvp-ar-guest-list-item-button"
		type="button"
		data-guest-number="1"
	>
		<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>		<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Main Guest		</span>
	</button>
</li>
	<script
	class="tribe-tickets__rsvp-ar-guest-list-item-template"
	id="tmpl-tribe-tickets__rsvp-ar-guest-list-item-template-5"
	type="text/template"
>
	<li
		class="tribe-tickets__rsvp-ar-guest-list-item"
		data-guest-number="{{data.attendee_id + 1}}"
	>
		<button
			class="tribe-tickets__rsvp-ar-guest-list-item-button tribe-tickets__rsvp-ar-guest-list-item-button--inactive"
			type="button"
			data-guest-number="{{data.attendee_id + 1}}"
		>
			<svg  class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon tribe-tickets__rsvp-ar-guest-icon--inactive"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs/><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"/></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-tickets__rsvp-ar-guest-list-item-title--inactive tribe-common-a11y-visual-hide">
				Guest			</span>
		</button>
	</li>
</script>

</ul>
';
