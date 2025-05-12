import React from 'react';
import { __ } from '@wordpress/i18n';
import { BaseControl } from '@wordpress/components';

/**
 * Interface for CurrencySelector props.
 *
 * @since TBD
 */
interface CurrencySelectorProps {
	currencies: Record<string, {
		symbol: string;
		name: string;
		code: string;
	}>;
	currencyCode: string;
	onCurrencyChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
	hasCountryWithSingleGateway: boolean;
}

/**
 * Component that renders a currency selector dropdown.
 *
 * @since TBD
 *
 * @param {CurrencySelectorProps} props Component props.
 *
 * @return {JSX.Element} The component.
 */
const CurrencySelector: React.FC<CurrencySelectorProps> = ({
	currencies,
	currencyCode,
	onCurrencyChange,
	hasCountryWithSingleGateway,
}) => {
	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="currency-code"
			label={ __( 'Currency', 'event-tickets' ) }
			className="tec-tickets-onboarding__form-field"
		>
			<select
				onChange={onCurrencyChange}
				value={currencyCode}
				required
			>
				{ Object.entries( currencies ).map( ( [ key, data ] ) => (
					<option key={ key } value={ data.code }>
						{ data.name } ({ data.code })
					</option>
				) ) }
			</select>
			{hasCountryWithSingleGateway && (
				<p className="tec-tickets-onboarding__currency-notice">
					{__('Currency selected based on your country.', 'event-tickets')}
				</p>
			)}
			<span className="tec-tickets-onboarding__required-label">
				{ __( 'Currency is required.', 'event-tickets' ) }
			</span>
			<span className="tec-tickets-onboarding__invalid-label">
				{ __( 'Currency is invalid.', 'event-tickets' ) }
			</span>
		</BaseControl>
	);
};

export default CurrencySelector;
