<?php
/**
 * Reindexing for the REST archive lists.
 *
 * @since TBD
 *
 * @package Tribe\Tickets
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase -- Carries the double-underscore naming the rest of this directory uses.

/**
 * Class Tribe__Tickets__REST__V1__Archive_List
 *
 * @since TBD
 */
final class Tribe__Tickets__REST__V1__Archive_List {
	/**
	 * Reindexes an archive list so it always encodes as a JSON array.
	 *
	 * Entries the repository could not format are dropped while their original keys are kept, and a
	 * filter can re-key the list again; either way the gaps make the list encode as a JSON object and
	 * consumers expecting an array stop rendering it.
	 *
	 * No native types: the value is deliberately untyped so a non-array reaches the caller unchanged
	 * instead of fataling, and `mixed` is not available on the PHP 7.4 this plugin still supports.
	 *
	 * @since TBD
	 *
	 * @param mixed $items The archive list to reindex.
	 *
	 * @return mixed The list reindexed from zero, or the value untouched when it is not an array.
	 */
	public static function reindex( $items ) {
		return is_array( $items ) ? array_values( $items ) : $items;
	}
}
