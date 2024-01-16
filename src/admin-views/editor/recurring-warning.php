<?php
/**
 * The template for the Series Pass event notice.
 *
 * @since TBD
 *
 * @var string $message The warning message.
 */
?>
<div class="ticket-editor-notice info info--background table-stick--before tec_ticket-panel__recurring-unsupported-warning" style="display: none">
	<span class="dashicons dashicons-lightbulb"></span>
	<span class="message">
		<?php
			echo wp_kses(
				$message,
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			);
			?>
	</span>
</div>
