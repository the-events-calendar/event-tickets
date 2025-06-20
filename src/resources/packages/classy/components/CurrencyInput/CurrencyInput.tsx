import React, { useState } from 'react';
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { LabeledInput } from '@tec/common/classy/components';
import { Currency } from '@tec/common/classy/types/Currency';
import { TicketComponentProps } from '../../types/TicketComponentProps';

// todo: Use the site settings for currency and position.
const defaultCurrency: Currency = {
	symbol: '$',
	position: 'prefix',
	code: 'USD',
};

// todo: Use the site settings for these.
const decimalPrecision = 2;
const decimalSeparator = '.';
const thousandSeparator = ',';

type CurrencyInputProps = {
	required?: boolean;
} & TicketComponentProps;

/**
 * Renders a currency input field in the Classy editor.
 *
 * @since TBD
 *
 * @param {TicketComponentProps} props
 * @return {JSX.Element} The rendered ticket price field.
 */
export default function CurrencyInput( props: CurrencyInputProps ): JSX.Element{

	const { label, onChange, value, required } = props;
	const defaultLabel = __( 'Price', 'event-tickets' );

	const [ hasFocus, setHasFocus ] = useState< boolean >( false );

	/*
	 * Todo: Rework this to use imask instead of a custom renderValue function.
	 *
	 * When I tried using the imask library, it didn't work as expected. Attempting to
	 * use any of the components from the library resulted in the entire ET Classy
	 * editor failing to load, so I reverted to a custom renderValue function.
	 */
	const renderValue = ( value: string ): string => {
		if ( hasFocus || value === '' ) {
			return value;
		}

		const pieces = value
			.replaceAll( thousandSeparator, '' )
			.split( decimalSeparator )
			.map( ( piece ) => piece.replace( /[^0-9]/g, '' ) )
			.filter( ( piece ) => piece !== '' );

		// The cleaned value should always use a period as the decimal separator.
		let cleanedValue = parseFloat( pieces.join( '.' ) );
		if ( isNaN( cleanedValue ) ) {
			cleanedValue = 0;
		}

		const formattedValue = cleanedValue.toFixed( decimalPrecision );

		return defaultCurrency.position === 'prefix'
			? `${ defaultCurrency.symbol }${ formattedValue }`
			: `${ formattedValue }${ defaultCurrency.symbol }`;
	};

	return (
		<LabeledInput label={ label || defaultLabel }>
			<InputControl
				className="classy-field__control classy-field__control--input"
				label={ label || defaultLabel }
				hideLabelFromVision={ true }
				value={ renderValue( value ) }
				onChange={ onChange }
				required={ required || false }
				onFocus={ (): void => setHasFocus( true ) }
				onBlur={ (): void => setHasFocus( false ) }
			/>
		</LabeledInput>
	);
}
