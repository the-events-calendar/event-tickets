<?php
/**
 * All Tickets list table search input template.
 *
 * @since TBD
 *
 * @var \Tribe__Template  $this                Current template object.
 * @var string            $search_id           The list table for the All Tickets screen.
 * @var string            $search_placeholder  Currently selected filter.
 * @var string            $search_value        Currently selected filter.
 */

?>
<input
	type="search"
	placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
	id="<?php echo esc_attr( $search_id ); ?>"
	name="s"
	value="<?php echo esc_attr( $search_value ); ?>"
	/>
