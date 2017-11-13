<?php
// If we don't have an Ticket ID we bail
if ( empty( $ticket ) ) {
	return;
}

$history = Tribe__Post_History::load( $ticket );

// Bail if there are no entries
if ( ! $history->has_entries() ) {
	return;
}

$entries = $history->get_entries();
?>

<div class="tribe-tickets-editor-history-container">
	<button class="accordion-header tribe-tickets-editor-history">
		<?php esc_html_e( 'History', 'event-tickets' ); ?>
	</button>
	<section id="tribe-tickets-editor-history" class="accordion-content">
		<h4 class="accordion-label screen_reader_text"><?php esc_html_e( 'Ti', 'event-tickets' ); ?></h4>
		<ul class="tribe-tickets-editor-history-list">
			<?php foreach ( $entries as $key => $entry ) : ?>
			<li>
				<?php echo tribe_format_date( $entry->datetime ); ?> | <?php echo $entry->message; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	</section>
</div>