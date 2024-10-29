<?php
/**
 * The template used to render empty map list in the center of the page with button to add a new map.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var string $add_new_url The URL to add a new map.
 */

$add_new_link = sprintf(
	'<a href="%s">%s</a>',
	esc_url( $add_new_url ),
	esc_html_x( 'Add a map', 'Add new seating map link', 'event-tickets' )
);
?>
<div class="tec-tickets__seating-tab--empty">
	<p>
	<?php
	echo wp_kses(
		sprintf(
			/* translators: %1$s: Add new link */
			__(
				'There are no maps available. %1$s',
				'event-tickets'
			),
			$add_new_link
		),
		[
			'a' => [
				'href' => [],
			],
		]
	);
	?>
	</p>
</div>
