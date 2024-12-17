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
 * Maps a fee to a select option.
 *
 * @since 5.18.0
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
	getFeeLabel,
	mapFeeToOption,
}
