<?php
// @todo: Discuss with @be @bordoni how we should approach the duplication here.
/**
 * Checks whether v2 of the Views is enabled or not.
 *
 * In order the function will check the `TRIBE_EVENTS_V2_VIEWS` constant,
 * the `TRIBE_EVENTS_V2_VIEWS` environment variable.
 *
 * @since 4.10.9
 *
 * @return bool Whether v2 of the Views are enabled or not.
 */
function tribe_events_tickets_views_v2_is_enabled() {
	if ( ! function_exists( 'tribe_events_views_v2_is_enabled' ) ) {
		return false;
	}

	return tribe_events_views_v2_is_enabled();
}

add_action( 'tribe_settings_before_content_tab_event-tickets', 'event_tickets_render_settings_banner' );

/**
 * Render the Help banner for the Ticket Settings Tab.
 *
 * @since TBD
 *
 * @return string
 */
function event_tickets_render_settings_banner() {

	$et_resource_links = [
		[
			'label' => esc_html__( 'Getting Started Guide', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/guide/event-tickets/',
		],

		[
			'label' => esc_html__( 'Configuring PayPal for Ticket Purchases', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/configuring-paypal-for-ticket-purchases/',
		],
		[
			'label' => esc_html__( 'Configuring Tribe Commerce', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/configuring-tribe-commerce/',
		],
		[
			'label' => esc_html__( 'Managing Orders and Attendees', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/tickets-managing-your-orders-and-attendees/',
		],
		[
			'label' => esc_html__( 'Event Tickets Manual', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/product/event-tickets/',
		],
	];

	$etp_resource_links = [
		[
			'label' => esc_html__( 'Tickets & WooCommerce', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/woocommerce-specific-ticket-settings/',
		],

		[
			'label' => esc_html__( 'Creating Tickets', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/making-tickets/',
		],
		[
			'label' => esc_html__( 'Event Tickets and Event Tickets Plus Settings Overview', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/k/settings-overview-event-tickets-and-event-tickets-plus/',
		],
		[
			'label' => esc_html__( 'Event Tickets Plus Manual', 'event-tickets' ),
			'href'  => 'https://theeventscalendar.com/knowledgebase/product/event-tickets-plus/',
		],
	];

	$context = [
		'etp_enabled'        => class_exists( 'Tribe__Tickets_Plus__Main' ),
		'et_resource_links'  => $et_resource_links,
		'etp_resource_links' => $etp_resource_links,
	];

	/** @var Tribe__Tickets__Admin__Views $admin_views */
	$admin_views = tribe( 'tickets.admin.views' );

	return $admin_views->template( 'settings/getting-started', $context );
}
