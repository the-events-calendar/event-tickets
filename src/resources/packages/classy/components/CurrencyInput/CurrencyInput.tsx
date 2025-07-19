import { CurrencyInput as CommonCurrencyInput } from '@tec/common/classy/components';
import { Currency } from '@tec/common/classy/types/Currency';
import { _x } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getCurrencySettings } from '../../localizedData';

const defaultCurrency: Currency = {
	symbol: decodeEntities( getCurrencySettings().symbol ),
	position: getCurrencySettings().position,
	code: getCurrencySettings().code,
};

const {
	decimalSeparator,
	thousandSeparator,
	precision: decimalPrecision,
} = getCurrencySettings();

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

	/**
	 * Whether the input field is required.
	 */
	required?: boolean;
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
		required = false,
	} = props;

	return (
		<CommonCurrencyInput
			label={ label || defaultLabel }
			onChange={ onChange }
			value={ value }
			required={ required }
			decimalPrecision={ decimalPrecision }
			decimalSeparator={ decimalSeparator }
			thousandSeparator={ thousandSeparator }
			defaultCurrency={ defaultCurrency }
		/>
	);
}
