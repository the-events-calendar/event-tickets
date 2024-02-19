<?php
/**
 * The template for the Series Pass event notice.
 *
 * @since 5.8.2
 *
 * @var array $messages The warning message.
 */

?>
<div class="ticket-editor-notice info info--background tec_ticket-panel__recurring-unsupported-warning" style="display: none">
	<span class="dashicons dashicons-lightbulb"></span>
	<div class="ticket-editor-notice_warning--messages">
	<?php foreach ( $messages as $key => $message ) : ?>
		<p class="ticket-editor-notice_warning--message <?php echo esc_attr( $key ); ?>">
		<?php
		echo wp_kses(
			$message,
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		);
		?>
		</p>
	<?php endforeach; ?>
	</div>
</div>
