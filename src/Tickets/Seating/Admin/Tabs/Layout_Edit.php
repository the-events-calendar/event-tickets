<?php
/**
 * The pseudo-tab used to create or edit a Layout.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Template;
use TEC\Tickets\Seating\Service\Service;
use WP_Error;
use TEC\Tickets\Seating\Meta;

/**
 * Class Layout_Edit.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */
class Layout_Edit extends Tab {
	/**
	 * The service used to render the iframe.
	 *
	 * @since TBD
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Layout_Edit constructor.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	public static function get_id(): string {
		return 'layout-edit';
	}

	/**
	 * Renders the tab.
	 *
	 * @since TBD
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	public function render(): void {
		$ephemeral_token = $this->service->get_ephemeral_token();
		$token           = is_string( $ephemeral_token ) ? $ephemeral_token : '';
		$context         = [
			'iframe_url' => $this->generate_iframe_url( $token ),
			'token'      => $token,
			'error'      => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
		];
		$this->template->template( 'tabs/layout-edit', $context );
	}
	
	/**
	 * Returns the URL to load the service route to edit a seat layout.
	 *
	 * @since TBD
	 *
	 * @param string $token     The ephemeral token used to secure the iframe communication with the service.
	 *
	 * @return string The URL to load the service route to edit a seat layout.
	 */
	public function generate_iframe_url( string $token ): string {
		$action    = tribe_get_request_var( 'action' );
		$layout_id = tribe_get_request_var( 'layoutId' );
		
		switch ( $action ) {
			case 'create':
				$map_id = tribe_get_request_var( 'mapId' );
				return $this->service->get_layout_create_url( $token, $map_id );
			case 'delete':
				return $this->service->get_layout_delete_url( $token, $layout_id );
			default:
				return $this->service->get_layout_edit_url( $token, $layout_id );
		}
	}

	/**
	 * Returns the URL to edit the Layout.
	 *
	 * @since TBD
	 *
	 * @param string $post_id The Post ID.
	 *
	 * @return string The URL to edit the Layout.
	 */
	public static function get_edit_url_by_post( string $post_id ): string {
		$layout_id = get_post_meta( $post_id, META::META_KEY_LAYOUT_ID, true );
		
		if ( empty( $layout_id ) ) {
			return '';
		}
		
		return add_query_arg(
			[
				'page'     => Admin::get_menu_slug(),
				'tab'      => self::get_id(),
				'layoutId' => $layout_id,
			],
			admin_url( 'admin.php' )
		);
	}
}
