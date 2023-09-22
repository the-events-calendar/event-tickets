<?php
/**
 * @var string $series_edit_link The markup of the link to edit the Series.
 * @var string $series_title     The title of the Series.
 */
?>

<div class="ticket-editor-notice info info--background table-stick--before series-pass-link">
	<span class="dashicons dashicons-lightbulb"></span>
	<span class="message">
		<?php echo wp_kses(
			sprintf(
				'Create and manage %1$s from the %2$s Series admin.',
				tec_tickets_get_series_pass_plural_uppercase(),
				'<a href="' . $series_edit_link . '" target="_blank">' . $series_title . '</a>',
			),
			[
				'a' => [
					'href'   => [],
					'target' => [],
				],
			]
		); ?>
	</span>
</div>