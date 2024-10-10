<?php
/**
 * All Tickets list table status filter template.
 *
 * @since 5.14.0
 *
 * @version 5.14.0
 *
 * @var \Tribe__Template  $this            Current template object.
 * @var string[]          $status_options  The list table for the All Tickets screen.
 * @var string            $current_status  Currently selected filter.
 */

?>
<select name="status-filter" id="tec-tickets-admin-tickets-status-filter">
	<?php foreach ( $status_options as $value => $label ) : ?>
		<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_status ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
	<?php endforeach; ?>
</select>
