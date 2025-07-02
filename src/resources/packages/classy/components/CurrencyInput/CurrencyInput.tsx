import { CurrencyInput as CommonCurrencyInput } from '@tec/common/classy/components/CurrencyInput';
import { Currency } from '@tec/common/classy/types/Currency';
import { _x } from '@wordpress/i18n';

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
	/**
	 * The label for the currency input field.
	 */
	label?: string;

	/**
	 * Callback function to handle changes in the input value.
	 */
	onChange: ( value: string ) => void;

	/**
	 * The current value of the input field.
	 */
	value?: string;
}

const defaultLabel = _x( 'Price', 'Label for the price input field', 'event-tickets' );

/**
 * Renders a currency input field in the Classy editor.
 *
 * @since TBD
 *
 * @param {CurrencyInputProps} props
 * @return {JSX.Element} The rendered currency input field.
 */
export default function CurrencyInput( props: CurrencyInputProps ): JSX.Element {
	const {
		label,
		onChange,
		value,
	} = props;

	return (
		<CommonCurrencyInput
			label={ label || defaultLabel }
			onChange={ onChange }
			value={ value }
			decimalPrecision={ decimalPrecision }
			decimalSeparator={ decimalSeparator }
			thousandSeparator={ thousandSeparator }
			defaultCurrency={ defaultCurrency }
		/>
	);
}
