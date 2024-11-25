/**
 * External dependencies.
 */
import { Checkbox } from "@moderntribe/common/elements";
import classNames from "classnames";

/**
 * @typedef {Object} Fee
 * @property {int} id
 * @property {string} display_name
 * @property {string} raw_amount
 * @property {string} status
 * @property {string} sub_type
 * @property {string} meta_value
 */

/**
 * Returns the fee label.
 *
 * @param {Fee} fee The fee object.
 * @returns {string} The fee label.
 */
const getFeeLabel = ( fee ) => {
	// Todo: the precision should be determined by settings.
	const amount = Number.parseFloat( fee.raw_amount ).toFixed( 2 );

	let feeLabel;
	if ( fee.sub_type === "percent" ) {
		feeLabel = `${fee.display_name} (${amount}%)`;
	} else {
		feeLabel = `${fee.display_name} ($${amount})`;
	}

	return feeLabel;
}

/**
 * Maps a fee to a checkbox item.
 *
 * @param {string} clientId The client ID of the ticket.
 * @param {Fee} fee The fee object to map.
 * @param {boolean} isChecked Whether the fee is checked.
 * @param {boolean} isDisabled Whether the fee is disabled.
 * @param {function} onChange The change handler for the fee.
 * @return {JSX.Element|null} The checkbox item, or null if the fee is not active.
 */
const mapFeeToItem = ( {
	clientId,
	fee,
	isChecked,
	isDisabled,
	onChange,
} ) => {
	// We shouldn't have these here, but just in case skip anything not active.
	if ( fee.status !== 'active' ) {
		return null;
	}

	// Todo: the precision should be determined by settings.
	const amount = Number.parseFloat( fee.raw_amount ).toFixed( 2 );

	const classes = [ 'tribe-editor__ticket__fee-checkbox' ];
	const name = `tec-ticket-fee-${ fee.id }-${ clientId }`;

	return (
		<Checkbox
			checked={ isChecked }
			className={ classNames( classes ) }
			disabled={ isDisabled }
			id={ name }
			label={ getFeeLabel( fee ) }
			onChange={ onChange }
			name={ name }
			value={ fee.id }
			key={ fee.id }
		/>
	);
};

/**
 * Maps a fee to a select option.
 *
 * @since TBD
 *
 * @param {Fee} fee
 * @returns {{label: string, value}}
 */
const mapFeeToOption = ( fee ) => {
	return {
		label: getFeeLabel( fee ),
		value: fee.id,
	};
};

export {
	mapFeeToItem,
	getFeeLabel,
	mapFeeToOption,
}
