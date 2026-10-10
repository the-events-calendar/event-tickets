/**
 * External dependencies
 */
import { useMemo, useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { subscribeToCommonStore } from './common-store-bridge';

/**
 * Returns a value read from the common store, read again whenever the store changes.
 *
 * The value is compared as JSON, so a read that builds a new object each time re-renders only when what it holds
 * changes.
 *
 * @since TBD
 *
 * @template T
 *
 * @param {function(): T} read Reads the value from the common store; it returns what JSON can hold.
 *
 * @return {T} The value.
 */
export function useCommonStoreValue( read ) {
	const snapshot = useSyncExternalStore( subscribeToCommonStore, () => JSON.stringify( read() ) );

	return useMemo( () => JSON.parse( snapshot ), [ snapshot ] );
}
