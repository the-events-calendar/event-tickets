<?php
/**
 * All Tickets list table search input template.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template  $this                Current template object.
 * @var string            $search_id           ID for the search input element.
 * @var string            $search_value        Current search value.
 */

?>
<input
	type="search"
	placeholder="<?php echo esc_attr__( 'Ticket or Event Name', 'event-tickets' ); ?>"
	id="<?php echo esc_attr( $search_id ); ?>"
	name="s"
	value="<?php echo esc_attr( $search_value ); ?>"
	/>
