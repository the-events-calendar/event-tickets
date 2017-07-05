<?php
/**
 * @var Tribe__Post_History $history
 * @var string $provider
 */
?>
<div class="ticket_advanced ticket_advanced_<?php echo esc_attr( $provider ); ?> history">
	<div>
		<label for="ticket_history"> <?php esc_html_e( 'Ticket history:', 'event-tickets' ) ?> </label>
	</div>
	<div>
		<a href="#" class="toggle-history">
			<span><?php esc_html_e( 'Click to view the history', 'event-tickets' ); ?></span>
			<span style="display:none"><?php esc_html_e( 'Click to hide history', 'event-tickets' ); ?></span>
		</a>
		<ul style="display:none">
			<?php foreach ( $history->get_entries() as $entry ): ?>
				<li>
					<span class="date"><?php echo esc_html( $entry->datetime ); ?> </span>
					<span class="details"><?php echo $entry->message; // No escaping: contains HTML formatting ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
