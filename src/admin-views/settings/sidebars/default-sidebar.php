<?php
/**
 * The default sidebar for the settings pages.
 *
 * @since 6.7.0
 */

declare( strict_types = 1 );

use TEC\Common\Admin\Entities\Br;
use TEC\Common\Admin\Entities\Heading;
use TEC\Common\Admin\Entities\Image;
use TEC\Common\Admin\Entities\Link;
use TEC\Common\Admin\Entities\Paragraph;
use TEC\Common\Admin\Entities\Plain_Text;
use TEC\Common\Admin\Settings_Section;
use TEC\Common\Admin\Settings_Sidebar;
use TEC\Common\Admin\Settings_Sidebar_Section;
use Tribe\Utils\Element_Attributes as Attributes;
use Tribe\Utils\Element_Classes as Classes;
use TEC\Tickets\Admin\Tickets\Page as Tickets_Page;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$break               = new Br();
$external_attributes = new Attributes(
	[
		'target' => '_blank',
		'rel'    => 'noopener',
	]
);

$sidebar = new Settings_Sidebar();

$hero_section = ( new Settings_Sidebar_Section() );
$hero_section->set_header_image(
	new Image(
		tribe_resource_url( 'images/settings_illustration.jpg', false, null, Tribe__Tickets__Main::instance() ),
		new Attributes(
			[
				'alt'  => '',
				'role' => 'presentation',
			]
		)
	)
);
$hero_section->set_title( new Heading( __( 'Finding and extending your tickets', 'tribe-common' ), 2, new Classes( 'tec-settings-form__sidebar-header' ) ) );

$hero_section->add_section(
	( new Settings_Section() )
		->add_elements(
			[
				( new Paragraph() )->add_child(
					new Plain_Text( __( 'Looking for additional functionality including recurring events, custom meta, community events, ticket sales, and more?', 'event-tickets' ) )
				),
				new Link(
					'https://evnt.is/products',
					__( 'Check out the available add-ons.', 'event-tickets' ),
					null,
					$external_attributes
				),
			]
		)
);

$hero_section->add_section(
	( new Settings_Section() )
		->set_title( new Heading( __( 'Documentation', 'event-tickets' ), 3 ) )
		->add_elements(
			[
				new Link(
					'https://evnt.is/1apn',
					__( 'Getting started guide', 'event-tickets' ),
					null,
					$external_attributes
				),
				$break,
				new Link(
					'https://evnt.is/1bbw',
					__( 'Knowledgebase', 'event-tickets' ),
					null,
					$external_attributes
				),
			]
		)
);

$hero_section->add_section(
	( new Settings_Section() )
		->add_elements(
			[
				( new Paragraph() )->add_child(
					new Plain_Text( __( 'Where are my tickets?', 'event-tickets' ) )
				),
				new Link(
					admin_url( 'admin.php?page=' . Tickets_Page::$slug ),
					__( 'Right here', 'event-tickets' )
				),
			]
		)
);

$hero_section->add_section(
	( new Settings_Section() )
		->add_elements(
			[
				( new Paragraph() )->add_child(
					new Plain_Text( __( 'Having trouble?', 'event-tickets' ) )
				),

				new Link(
					admin_url( 'edit.php?post_type=tec-tickets-settings&page=tec-tickets-help-hub' ),
					__( 'Help', 'event-tickets' )
				),
				$break,
				new Link(
					admin_url( 'admin.php?page=tec-tickets-troubleshooting' ),
					__( 'Troubleshoot', 'event-tickets' )
				),
			]
		)
);

$sidebar->add_section( $hero_section );

return $sidebar;
