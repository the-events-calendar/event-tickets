/**
 * Debounce hook for React
 *
 * @since TBD
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Hook to debounce a value
 *
 * @since TBD
 *
 * @param {any} value The value to debounce.
 * @param {number} delay The delay in milliseconds.
 *
 * @return {any} The debounced value.
 */
export function useDebounce( value, delay ) {
	const [ debouncedValue, setDebouncedValue ] = useState( value );

	useEffect( () => {
		const handler = setTimeout( () => {
			setDebouncedValue( value );
		}, delay );

		return () => {
			clearTimeout( handler );
		};
	}, [ value, delay ] );

	return debouncedValue;
}