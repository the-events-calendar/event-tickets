<?php
/**
 * All Tickets list table select filter template.
 *
 * @since TBD
 *
 * @var \Tribe__Template  $this            Current template object.
 * @var string[]          $select_options  The list table for the All Tickets screen.
 * @var string            $current_filter  Currently selected filter.
 */

?>
<select name="ticket-filter" id="tec-tickets-all-tickets-select-filter">
	<?php foreach ( $select_options as $value => $label ) : ?>
		<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_filter ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
	<?php endforeach; ?>
</select>
