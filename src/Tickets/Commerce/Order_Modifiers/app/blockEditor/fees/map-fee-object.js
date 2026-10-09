/**
 * External dependencies.
 */

/**
 * @typedef {Object} Fee
 * @property {number} id           The fee ID.
 * @property {string} display_name The fee display name.
 * @property {string} raw_amount   The raw fee amount.
 * @property {string} status       The fee status.
 * @property {string} sub_type     The fee sub type.
 * @property {string} meta_value   The fee meta value.
 */

/**
 * Returns the fee label.
 *
 * @param {Fee} fee The fee object.
 * @return {string} The fee label.
 */
const getFeeLabel = ( fee ) => {
	// Todo: the precision should be determined by settings.
	const amount = Number.parseFloat( fee.raw_amount ).toFixed( 2 );

	let feeLabel;
	if ( fee.sub_type === 'percent' ) {
		feeLabel = `${ fee.display_name } (${ amount }%)`;
	} else {
		feeLabel = `${ fee.display_name } ($${ amount })`;
	}

	return feeLabel;
};

/**
 * Maps a fee to a select option.
 *
 * @since 5.18.0
 *
 * @param {Fee} fee The fee to convert.
 * @return {{label: string, value}} The select option.
 */
const mapFeeToOption = ( fee ) => {
	return {
		label: getFeeLabel( fee ),
		value: fee.id,
	};
};

export { getFeeLabel, mapFeeToOption };
