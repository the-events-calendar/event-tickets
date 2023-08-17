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
				'This %1$s is part of the Series %2$s. Create and manage %3$s in the Series this %1$s belongs to.',
				tribe_get_event_label_singular_lowercase(),
				'<a href="' . $series_edit_link . '" target="_blank">' . $series_title . '</a>',
				tec_tickets_get_series_pass_plural_uppercase(),
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