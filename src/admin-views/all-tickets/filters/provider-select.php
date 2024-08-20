<?php
/**
 * All Tickets list table provider filter template.
 *
 * @since TBD
 *
 * @var \Tribe__Template  $this                 Current template object.
 * @var string[]          $provider_options     The list table for the All Tickets screen.
 * @var string            $current_provider     Currently selected filter.
 * @var bool              $show_provider_filter Whether to show the provider filter.
 */

if ( ! $show_provider_filter ) {
	return;
}

?>
<select name="provider-filter" id="tec-tickets-all-tickets-provider-filter">
	<?php foreach ( $select_options as $value => $label ) : ?>
		<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_filter ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
	<?php endforeach; ?>
</select>
