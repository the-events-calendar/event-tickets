<?php
/**
 * Tells the block editor that ticket saves are deferred.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */

namespace TEC\Tickets\Deferred_Save\Block;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Editor_Config.
 *
 * Adds `usesDeferredSave` to the tickets editor configuration Event Tickets already prints for the
 * block editor, read in JavaScript as `globals.tickets().usesDeferredSave`. The key is only present
 * while the feature is active, so its presence is the editor's signal that saves are deferred.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */
final class Editor_Config extends Controller_Contract {
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
	 * Adds the deferred save flag.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $localized The editor configuration.
	 *
	 * @return array<string,mixed> The configuration with `usesDeferredSave`.
	 */
	public function add_flag( $localized ): array {
		$localized                     = is_array( $localized ) ? $localized : [];
		$localized['usesDeferredSave'] = true;

		return $localized;
	}
}
