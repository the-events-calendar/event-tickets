<?php
/**
 * The pseudo-tab used to create or edit a Controller Configuration.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin\Template;
use TEC\Tickets\Seating\Service\Service;
use WP_Error;

/**
 * Class Map_Edit.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Admin\Tabs;
 */
class Map_Edit extends Tab {
	/**
	 * The service used to render the iframe.
	 *
	 * @since 5.16.0
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Map_Edit constructor.
	 *
	 * since 5.16.0
	 *
	 * @param Template $template A reference to the template handle used to render this tab.
	 * @param Service  $service A reference to the service object.
	 */
	public function __construct( Template $template, Service $service ) {
		parent::__construct( $template );
		$this->service = $service;
	}

	/**
	 * Returns the title of this tab. The one that will be displayed on the top of the page.
	 *
	 * @since 5.16.0
	 *
	 * @return string The title of this tab.
	 */
	public function get_title(): string {
		// No title for this pseudo-tab.
		return '';
	}

	/**
	 * Returns the ID of this tab, used in the URL and CSS/JS attributes.
	 *
	 * @since 5.16.0
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	public static function get_id(): string {
		return 'map-edit';
	}

	/**
	 * Renders the tab.
	 *
	 * @since 5.16.0
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	public function render(): void {
		$map_id          = tribe_get_request_var( 'mapId' );
		$ephemeral_token = $this->service->get_ephemeral_token( 6 * HOUR_IN_SECONDS, 'admin' );
		$token           = is_string( $ephemeral_token ) ? $ephemeral_token : '';
		$iframe_url      = $map_id ? $this->service->get_map_edit_url( $token, $map_id )
			: $this->service->get_map_create_url( $token );
		$context         = [
			'iframe_url' => $iframe_url,
			'token'      => $token,
			'error'      => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
		];
		$this->template->template( 'tabs/map-edit', $context );
	}
}
