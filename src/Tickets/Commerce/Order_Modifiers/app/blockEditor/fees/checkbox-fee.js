/**
 * External dependencies.
 */
import classNames from "classnames";
import { Checkbox, CheckboxInput, } from "@moderntribe/common/elements";
import { LabelWithTooltip } from '@moderntribe/tickets/elements';
import { Dashicon } from "@wordpress/components";

/**
 * Internal dependencies.
 */
import { getFeeLabel } from "./map-fee-object";

/**
 * Get the name attribute for the checkbox.
 *
 * @param {string} clientId
 * @param {Fee} fee
 * @returns {`tec-ticket-fee-${string}-${string}`}
 */
const getCheckboxName = ( clientId, fee ) => {
	return `tec-ticket-fee-${ fee.id }-${ clientId }`;
};


/**
 * Get the container classes for the checkbox.
 *
 * @returns {[string]}
 */
const getContainerClasses = () => {
	return [ 'tribe-editor__ticket__fee-checkbox' ];
}

/**
 * CheckboxFee component.
 *
 * @param {string} clientId The client ID of the ticket.
 * @param {Fee} fee The fee object to map.
 * @param {boolean} isChecked Whether the fee is checked.
 * @param {boolean} isDisabled Whether the fee is disabled.
 * @param {function} onChange The change handler for the fee.
 * @return {JSX.Element|null} The checkbox item, or null if the fee is not active.
 */
const CheckboxFee = ( {
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

	const name = getCheckboxName( clientId, fee );

	return (
		<Checkbox
			checked={ isChecked }
			className={ classNames( getContainerClasses() ) }
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

export {
	CheckboxFee,
};
