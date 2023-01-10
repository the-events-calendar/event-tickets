<?php
/**
 * @var array $settings List of display settings.
 */

$rsvp_new_views_upgrade_option_available    = tribe_installed_before( 'Tribe__Tickets__Main', '5.0' );
$tickets_new_views_upgrade_option_available = tribe_installed_before( 'Tribe__Tickets__Main', '5.0.3' );

// Don't display this section if ET is a newer install and ETP is inactive or ETP is active but a newer install.
if ( ! $rsvp_new_views_upgrade_option_available && ! $tickets_new_views_upgrade_option_available ) {
	return;
}

$tickets_rsvp_display_title = esc_html__( 'Display', 'event-tickets' );

$tickets_rsvp_display_description = esc_html(
	sprintf(
	// Translators: %1$s: dynamic "RSVPs" text.
		_x(
			'The settings below control the display of your %1$s.',
			'description of settings section',
			'event-tickets'
		),
		tribe_get_rsvp_label_plural( 'tickets_rsvp_display_description' )
	)
);

if ( $tickets_new_views_upgrade_option_available ) {
	$tickets_rsvp_display_description = esc_html(
		sprintf(
			// Translators: %1$s: dynamic "Tickets" text, %2$s: dynamic "RSVPs" text.
			_x(
				'The settings below control the display of your %1$s and %2$s.',
				'description of settings section',
				'event-tickets'
			),
			esc_html( tribe_get_ticket_label_plural( 'tickets_rsvp_display_description' ) ),
			tribe_get_rsvp_label_plural( 'tickets_rsvp_display_description' )
		)
	);
}

$et_options_display = [
	'tickets_rsvp_display_title'       => [
		'type' => 'html',
		'html' => '<h3 id="tickets_rsvp_display_title">' . $tickets_rsvp_display_title . '</h3>',
	],
	'tickets_rsvp_display_description' => [
		'type' => 'html',
		'html' => '<p>' . $tickets_rsvp_display_description . '</p>',
	],
];

// Only show this option to older installs, as newer installs default to new views.
if ( $rsvp_new_views_upgrade_option_available ) {
	$et_options_display['tickets_rsvp_use_new_views'] = [
		'type'            => 'checkbox_bool',
		'label'           => esc_html(
			sprintf(
			// Translators: %1$s: dynamic "RSVP" text.
				_x(
					'Enable New %1$s Experience',
					'settings label',
					'event-tickets'
				),
				tribe_get_rsvp_label_singular( 'tickets_rsvp_use_new_views' )
			)
		),
		'tooltip'         => esc_html(
			sprintf(
			// Translators: %1$s: dynamic "RSVP" text.
				_x(
					'This setting will render the new front-end designs (styling) and user-flow for the %1$s experience.',
					'settings tooltip',
					'event-tickets'
				),
				tribe_get_rsvp_label_singular( 'tickets_rsvp_use_new_views' )
			)
		),
		'validation_type' => 'boolean',
		'default'         => false,
	];
}

// Only show this option to older installs, as newer installs default to new views.
if ( $tickets_new_views_upgrade_option_available ) {
	$et_options_display['tickets_use_new_views'] = [
		'type'            => 'checkbox_bool',
		'label'           => esc_html(
			sprintf(
			// Translators: %1$s: dynamic "Tickets" text.
				_x(
					'Enable Updated %1$s Experience',
					'settings label',
					'event-tickets'
				),
				tribe_get_ticket_label_plural( 'tickets_use_new_views' )
			)
		),
		'tooltip'         => wp_kses_post(
			sprintf(
				// Translators: %1$s: dynamic "Tickets" text, %2$s and %3$s are part of link.
				_x( 'Enables updated front-end views and Individual Attendee Collection flows for %1$s. You\'ll see a new default setting within the Tickets tab and new individual ticket setting within both Classic and Gutenberg editors %2$s[Learn more]%3$s.', 'settings tooltip', 'event-tickets' ),
				tribe_get_ticket_label_plural( 'tickets_use_new_views' ),
				'<a href="https://evnt.is/1an-">',
				'</a>'
			)
		),
		'validation_type' => 'boolean',
		'default'         => false,
	];
}

$settings = Tribe__Main::array_insert_before_key(
	'tribe-form-content-end',
	$settings,
	$et_options_display
);

return $settings;
