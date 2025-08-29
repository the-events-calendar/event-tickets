/**
 * Capacity field component for RSVP block
 *
 * @since TBD
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { useDebounce } from '../../hooks/useDebounce';

/**
 * Capacity field component
 *
 * @since TBD
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.value    Current capacity value.
 * @param {Function} props.onChange Callback when value changes.
 * @param {boolean}  props.disabled Whether the field is disabled.
 *
 * @return {JSX.Element} The capacity field component.
 */
export function CapacityField( { value, onChange, disabled = false } ) {
	const [ localValue, setLocalValue ] = useState( value || '' );
	const debouncedValue = useDebounce( localValue, 500 );
	const isTyping = useRef( false );

	// Sync debounced value with parent (only when actually typing)
	useEffect( () => {
		if ( isTyping.current && debouncedValue !== value ) {
			onChange( debouncedValue );
			isTyping.current = false;
		}
	}, [ debouncedValue, onChange, value ] );

	// Sync external value changes with local state (only when not typing)
	useEffect( () => {
		if ( ! isTyping.current ) {
			setLocalValue( value || '' );
		}
	}, [ value ] );

	/**
	 * Handle input change
	 *
	 * @param {string} newValue The new input value.
	 */
	const handleChange = ( newValue ) => {
		isTyping.current = true;
		
		// Allow empty string for unlimited
		if ( newValue === '' ) {
			setLocalValue( '' );
			return;
		}

		// Only allow positive integers
		if ( /^\d+$/.test( newValue ) ) {
			setLocalValue( newValue );
		}
	};

	return (
		<TextControl
			label={ __( 'Capacity', 'event-tickets' ) }
			value={ localValue }
			onChange={ handleChange }
			placeholder={ __( 'Leave blank for unlimited', 'event-tickets' ) }
			help={ __( 'Maximum number of RSVPs allowed', 'event-tickets' ) }
			disabled={ disabled }
			type="text"
			inputMode="numeric"
			pattern="[0-9]*"
			className="tec-rsvp-block__capacity-field"
		/>
	);
}