import React, { Fragment, useRef, useState } from 'react';
import { useIMask, IMaskInput } from 'react-imask';
import { __experimentalInputControl as InputControl } from '@wordpress/components';

import { __ } from '@wordpress/i18n';
import { LabeledInput } from '@tec/common/classy/components';
import { Currency } from '@tec/common/classy/types/Currency';
import { CurrencyPosition } from '@tec/common/classy/types/CurrencyPosition';
import { TicketComponentProps } from '../../types/TicketComponentProps';


export default function TicketPrice( props: TicketComponentProps ): JSX.Element{

	const { label, onChange, value } = props;
	const defaultLabel = __( 'Ticket price', 'event-tickets' );

	const [ hasFocus, setHasFocus ] = useState< boolean >( false );

	// todo: Use the site settings for currency and position.
	const defaultCurrency: Currency = {
		symbol: '$',
		position: 'prefix',
		code: 'USD',
	};

	// todo: Use the site settings for decimal places.
	const decimalPlaces = 2;

	// todo: Use the site settings for decimal separator.
	const decimalSeparator = '.';

	const maskRef = useRef( null );
	const maskValueRef = useRef( value || '' );

	// const [ iMaskOptions, setIMaskOptions ] = useState( {
	// 	mask: Number,
	// 	value: value || '',
	// 	radix: decimalSeparator,
	//
	// } );
	// const {
	// 	ref,
	// } = useIMask( iMaskOptions );

	// todo: consider making this a Common component.
	const renderValue = ( value: string ): string => {
		if ( hasFocus || value === '' ) {
			return value;
		}

		return defaultCurrency.position === 'prefix'
			? `${ defaultCurrency.symbol }${ value }`
			: `${ value }${ defaultCurrency.symbol }`;
	};

	return (
		<LabeledInput label={ label || defaultLabel }>
			<InputControl
				className="classy-field__control classy-field__control--input"
				label={ label || defaultLabel }
				hideLabelFromVision={ true }
				value={ value }
				onChange={ onChange }
				required={ true }
				onFocus={ (): void => setHasFocus( true ) }
				onBlur={ (): void => setHasFocus( false ) }
			/>
			<IMaskInput
				ref={ maskRef }
				inputRef={ maskValueRef }
				mask={ Number }
				value={ value }
				onAccept={ ( value: string ) => onChange( value ) }
			/>
		</LabeledInput>
	);
}
