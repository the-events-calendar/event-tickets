<?php
/**
 * @var string $series_edit_link The markup of the link to edit the Series.
 * @var string $helper_link     The markup of the link to the help article.
 */

?>

<div class="ticket-editor-notice info info--background table-stick--before series-pass-link">
	<span class="dashicons dashicons-lightbulb"></span>
	<span class="message">
		<?php 
		echo wp_kses(
			sprintf(
				/* Translators: %1$s: Event label singular lowercase, %2$s: Series Pass plural uppercase, %3$s: Series edit link, %4$s: <br>, %5$s: helper link */
				'This %1$s is part of a Series. Create and manage %2$s from the %3$s Series admin. %4$s %5$s',
				tribe_get_event_label_singular_lowercase(),
				tec_tickets_get_series_pass_plural_uppercase( 'series pass event notice' ),
				$series_edit_link,
				'<br>',
				$helper_link,
			),
			[
				'br' => [],
				'a'  => [
					'href'   => [],
					'target' => [],
				],
			]
		); 
		?>
	</span>
</div>
