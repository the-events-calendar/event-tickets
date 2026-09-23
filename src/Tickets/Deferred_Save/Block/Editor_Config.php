<?php
/**
 * Tells the block editor whether the post it edits uses deferred save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */

namespace TEC\Tickets\Deferred_Save\Block;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Deferred_Save\Controller;

/**
 * Class Editor_Config.
 *
 * Adds `usesDeferredSave` to the tickets editor configuration Event Tickets already prints for the
 * block editor, read in JavaScript as `globals.tickets().usesDeferredSave`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */
class Editor_Config extends Controller_Contract {
	/**
	 * Hooks the editor configuration.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_editor_configuration_localized_data', [ $this, 'add_flag' ] );
	}

	/**
	 * Unhooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_editor_configuration_localized_data', [ $this, 'add_flag' ] );
	}

	/**
	 * Adds whether the post being edited uses deferred save.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $localized The editor configuration.
	 *
	 * @return array<string,mixed> The configuration with `usesDeferredSave`.
	 */
	public function add_flag( $localized ): array {
		$post_id = (int) get_the_ID();

		$localized                     = is_array( $localized ) ? $localized : [];
		$localized['usesDeferredSave'] = $post_id > 0 && $this->container->get( Controller::class )->uses_deferred_save( $post_id );

		return $localized;
	}
}
