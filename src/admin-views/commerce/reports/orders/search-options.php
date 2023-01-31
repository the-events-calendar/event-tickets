<?php
/**
 * The template that displays the search type dropdown in the Tickets Commerce Order report admin screen.
 *
 * @version 5.5.6
 *
 * @var array $options Available options.
 * @var string $selected Selected value.
 */
?>
<select name="tec_tc_order_search_type" class="tec_tc_order_search_type">
	<?php foreach ( $options as $option => $label ) : ?>
		<option value="<?php echo esc_attr( $option ); ?>"<?php selected( $selected, $option ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
	<?php endforeach; ?>
</select>