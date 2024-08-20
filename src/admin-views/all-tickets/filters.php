<?php
/**
 * All Tickets list table select filter template.
 *
 * @since TBD
 *
 * @var \Tribe__Template  $this            Current template object.
 * @var string[]          $select_options  The list table for the All Tickets screen.
 * @var string            $current_filter  Currently selected filter.
 * @var string            $search_id       ID for the search input element.
 * @var string            $search_value    Current search value.
 */

?>
<div class="alignleft actions">
	<?php $this->template( 'all-tickets/filters/select-box' ); ?>
	<?php $this->template( 'all-tickets/filters/search-input' ); ?>
	<?php submit_button( esc_html__( 'Show Tickets', 'event-tickets' ), 'button', false, false, [ 'id' => 'tec-tickets-all-tickets-select-filter' ] ); ?>
</div>
