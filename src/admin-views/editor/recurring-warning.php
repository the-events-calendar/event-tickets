<?php
/**
 * The template for the Series Pass event notice.
 *
 * @since TBD
 *
 * @var array $messages The warning message.
 */

?>
<div class="ticket-editor-notice info info--background table-stick--before tec_ticket-panel__recurring-unsupported-warning" style="display: none">
	<span class="dashicons dashicons-lightbulb"></span>
	<div class="ticket-editor-notice_warning--messages">
	<?php foreach ( $messages as $key => $message ) : ?>
		<p class="ticket-editor-notice_warning--message <?php echo esc_attr( $key ); ?>">
		<?php echo wp_kses_post( $message ); ?>
		</p>
	<?php endforeach; ?>
	</div>
</div>
