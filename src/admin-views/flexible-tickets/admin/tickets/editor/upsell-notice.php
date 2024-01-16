<?php
/**
 * Ticket type upsell notice on new Ticket editor.
 *
 * @version 5.8.0
 */
$upgrade_link = sprintf(
	// translators: %1$s: URL for upgrade link, %2$s: Label for upgrade link.
	'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
	esc_url( 'https://evnt.is/tt-ecp' ),
	esc_html_x( 'upgrade', 'Upgrade link for ticket types', 'event-tickets' )
);

$notice = sprintf(
	__( 'For more ticket types, %1$s to Events Calendar Pro.', 'event-tickets' ),
	$upgrade_link
);

$icon_url = tribe_resource_url( 'images/icons/circle-bolt.svg', false, null, Tribe__Main::instance() );
?>
<div class="tec_tickets_editor__header__ticket-type-upsell-notice">
	<img class="tec_tickets_editor__header__ticket-type-upsell-icon" src="<?php echo esc_url( $icon_url ) ?>" alt="upsell-icon">
	<span>
		<?php echo wp_kses( $notice, [
			'a' => [
				'class'  => [],
				'href'   => [],
				'target' => [],
				'rel'    => [],
			]
		] ); ?>
	</span>
</div>