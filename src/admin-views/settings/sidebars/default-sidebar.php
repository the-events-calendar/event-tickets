<?php
/**
 * The default sidebar for the settings pages.
 *
 * @since 6.7.0
 */

declare( strict_types=1 );

use TEC\Common\Admin\Entities\Heading;
use TEC\Common\Admin\Entities\Image;
use TEC\Common\Admin\Entities\Link;
use TEC\Common\Admin\Entities\Paragraph;
use TEC\Common\Admin\Entities\Plain_Text;
use TEC\Common\Admin\Entities\Unordered_List;
use TEC\Common\Admin\Entities\List_Item;
use TEC\Common\Admin\Settings_Section;
use TEC\Common\Admin\Settings_Sidebar;
use TEC\Common\Admin\Settings_Sidebar_Section;
use Tribe\Utils\Element_Attributes as Attributes;
use Tribe\Utils\Element_Classes as Classes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$external_attributes = new Attributes(
	[
		'target' => '_blank',
		'rel'    => 'noopener',
	]
);

$sidebar      = new Settings_Sidebar();
$hero_section = new Settings_Sidebar_Section();

// Set Hero Image and Title.
$hero_section
	->set_header_image(
		new Image(
			tribe_resource_url(
				'images/settings_illustration.jpg',
				false,
				null,
				Tribe__Tickets__Main::instance()
			),
			new Attributes(
				[
					'alt'  => '',
					'role' => 'presentation',
				]
			)
		)
	)
	->set_title(
		new Heading(
			__( 'Supercharging your tickets', 'event-tickets' ),
			2,
			new Classes( 'tec-settings-form__sidebar-header' ),
			new Attributes( [ 'id' => 'tec-settings-sidebar-title' ] )
		)
	);

// Build Beginner Resources List.
$beginner_list = new Unordered_List(
	new Classes( 'tec-tickets__admin-banner-kb-list' )
);

$beginner_list->add_children(
	[
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1aot', __( 'Getting Started Guide', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1aoz', __( 'Event Tickets Manual', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1axs', __( 'What is Tickets Commerce?', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1axt', __( 'Configuring Tickets Commerce', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1aox', __( 'Using RSVPs', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1aoy', __( 'Managing Orders and Attendees', 'event-tickets' ), null, $external_attributes )
		),
	]
);

// Build Advanced Plus Features List.
$advanced_list = new Unordered_List(
	new Classes( 'tec-tickets__admin-banner-kb-list' )
);

$advanced_list->add_children(
	[
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1ap0', __( 'Setting Up E-Commerce Plugins for Selling Tickets', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1ap1', __( 'Tickets & WooCommerce', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1ap2', __( 'Creating Tickets', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1ap3', __( 'Event Tickets and Event Tickets Plus Settings Overview', 'event-tickets' ), null, $external_attributes )
		),
		( new List_Item( null, null ) )->add_child(
			new Link( 'https://evnt.is/1ap4', __( 'Event Tickets Plus Manual', 'event-tickets' ), null, $external_attributes )
		),
	]
);

// Build Full Sidebar Content.
$hero_section->add_section(
	( ( new Settings_Section() )->add_elements(
		[
			// Thank you paragraph.
			( new Paragraph() )->add_child(
				new Plain_Text(
					__( 'Thank you for using Event Tickets! We recommend looking through the settings below so that you can fine-tune your specific ticketing needs. Here are some resources that can help.', 'event-tickets' )
				)
			),
			// Empty paragraph to add vertical space.
			( new Paragraph() )->add_child(
				new Plain_Text( '' )
			),
			// Beginner Resources heading.
			( new Paragraph() )->add_child(
				new Plain_Text(
					__( 'Beginner Resources', 'event-tickets' )
				)
			),
			// Beginner List.
			$beginner_list,
			// Advanced Plus Features heading.
			( new Paragraph() )->add_child(
				new Plain_Text(
					__( 'Advanced Plus Features', 'event-tickets' )
				)
			),
			// Advanced List.
			$advanced_list,
		]
	) )
);

$sidebar->add_section( $hero_section );

return $sidebar;
