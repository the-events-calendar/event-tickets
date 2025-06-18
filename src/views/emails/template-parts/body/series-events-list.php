<?php
/**
 * Event Tickets Emails: Main template > Body > Series Events List
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/series-events-list.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.8.4
 *
 * @since 5.8.4
 *
 * @var bool           $show_events_in_email Whether to show the events in the email or not.
 * @var string         $title                The upcoming Events list title.
 * @var int            $series_id            The post ID of the Series the Pass is related to and the Events list is being
 *                                           printed for.
 * @var array<WP_Post> $events               The list of upcoming Series Events.
 * @var string|null    $series_link          The link to the series page, or `null` if the Events displayed are all those
 *                                           current available.
 * @var string         $series_link_text     The text for the series link.
 *
 * @see     tribe_get_event for the augmented WP_Post object returned in the `$events` array.
 */

if ( ! $show_events_in_email ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-content-upcoming-events-list__title-container">
		<header
			class="tec-tickets__email-table-content__section-header tec-tickets__email-table-content__section-header--upcoming-events-list">
			<?php echo esc_html( $title ); ?>
		</header>
	</td>
</tr>

<tr class="tec-tickets__email-table-content-upcoming-events-list__cards-container">
	<td>
		<?php foreach ( $events as $event ) : ?>
			<table class="tec-tickets__email-table-content-upcoming-event-card">
				<tr class="tec-tickets__email-table-content-upcoming-event-card__line-1">
					<td class="tec-tickets__email-table-content-upcoming-event-card__month">
						<?php echo esc_html( strtoupper( $event->dates->start->format_i18n( 'M' ) ) ); ?>
					</td>
					<td class="tec-tickets__email-table-content-upcoming-event-card__time">
						<?php echo esc_html( tribe_get_start_time( $event->ID ) ) ?>
						-
						<?php echo esc_html( tribe_get_end_time( $event->ID ) ) ?>
					</td>
				</tr>
				<tr class="tec-tickets__email-table-content-upcoming-event-card__line-2">
					<td class="tec-tickets__email-table-content-upcoming-event-card__day">
						<?php echo esc_html( $event->dates->start->format_i18n( 'd' ) ); ?>
					</td>
					<td class="tec-tickets__email-table-content-upcoming-event-card__title">
						<?php echo esc_html( $event->post_title ); ?>
					</td>
				</tr>
			</table>
		<?php endforeach; ?>

		<?php if ( ! empty ( $series_link ) ) : ?>
			<div class="tec-tickets__email-table-content-upcoming-event-card__link">
				<a href="<?php echo esc_url( $series_link ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $series_link_text ); ?>
				</a>
			</div>
		<?php endif; ?>
	</td>
</tr>
