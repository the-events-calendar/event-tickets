<?php
/**
 * Tells the block editor that ticket saves are deferred.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */

namespace TEC\Tickets\Deferred_Save\Block;

/**
 * Class Editor_Config.
 *
 * Adds `usesDeferredSave` to the tickets editor configuration Event Tickets already prints for the
 * block editor, read in JavaScript as `globals.tickets().usesDeferredSave`. The feature Controller
 * hooks it only while the feature is active, so the key's presence is the editor's signal that saves
 * are deferred.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Block
 */
final class Editor_Config {
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
