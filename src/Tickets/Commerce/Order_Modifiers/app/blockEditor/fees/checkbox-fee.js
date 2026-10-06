/**
 * External dependencies.
 */
import classNames from 'classnames';
import { CheckboxInput } from '@moderntribe/common/elements';
import { LabelWithTooltip } from '../../../../../../modules/elements';
import { Dashicon } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { getFeeLabel } from './map-fee-object';

/**
 * @typedef {import('./map-fee-object').Fee} Fee
 */

/**
 * Get the name attribute for the checkbox.
 *
 * @param {string} clientId The client ID of the ticket.
 * @param {Fee}    fee      The fee object.
 * @return {string} The checkbox name, in the form `tec-ticket-fee-{feeId}-{clientId}`.
 */
const getCheckboxName = ( clientId, fee ) => {
	return `tec-ticket-fee-${ fee.id }-${ clientId }`;
};

/**
 * Get the container classes for the checkbox.
 *
 * @return {string[]} The container class names.
 */
const getContainerClasses = () => {
	return [ 'tribe-editor__ticket__fee-checkbox' ];
};

/**
 * CheckboxFee component.
 *
 * @param {Object}   props            The component properties.
 * @param {string}   props.clientId   The client ID of the ticket.
 * @param {Fee}      props.fee        The fee object to map.
 * @param {boolean}  props.isChecked  Whether the fee is checked.
 * @param {boolean}  props.isDisabled Whether the fee is disabled.
 * @param {Function} props.onChange   The change handler for the fee.
 * @return {JSX.Element|null} The checkbox item, or null if the fee is not active.
 */
const CheckboxFee = ( { clientId, fee, isChecked, isDisabled, onChange } ) => {
	// We shouldn't have these here, but just in case skip anything not active.
	if ( fee.status !== 'active' ) {
		return null;
	}

	const name = getCheckboxName( clientId, fee );

	return (
		<div className={ classNames( 'tribe-editor__checkbox', getContainerClasses() ) }>
			<CheckboxInput
				checked={ isChecked }
				className="tribe-editor__checkbox__input"
				disabled={ isDisabled }
				id={ name }
				name={ name }
				onChange={ onChange }
				value={ fee.id }
				key={ fee.id }
			/>
			<LabelWithTooltip forId={ name } isLabel={ true } label={ getFeeLabel( fee ) } />
		</div>
	);
};

/**
 * CheckboxFeeWithTooltip component.
 *
 * @param {Object}   props             The component properties.
 * @param {string}   props.clientId    The client ID of the ticket.
 * @param {Fee}      props.fee         The fee object.
 * @param {boolean}  props.isChecked   Whether the fee is checked.
 * @param {boolean}  props.isDisabled  Whether the fee is disabled.
 * @param {Function} props.onChange    The change handler for the fee.
 * @param {string}   props.tooltipText The tooltip text.
 * @return {JSX.Element} The checkbox item with a tooltip.
 */
const CheckboxFeeWithTooltip = ( { clientId, fee, isChecked, isDisabled, onChange, tooltipText } ) => {
	if ( 'undefined' === typeof onChange ) {
		onChange = () => {};
	}

	const name = getCheckboxName( clientId, fee );

	return (
		<div className={ classNames( 'tribe-editor__checkbox', getContainerClasses() ) }>
			<CheckboxInput
				checked={ isChecked }
				className="tribe-editor__checkbox__input"
				disabled={ isDisabled }
				id={ name }
				name={ name }
				onChange={ onChange }
				value={ fee.id }
			/>
			<LabelWithTooltip
				forId={ name }
				isLabel={ true }
				label={ getFeeLabel( fee ) }
				tooltipText={ tooltipText }
				tooltipLabel={
					tooltipText && <Dashicon className="tribe-editor__ticket__tooltip-label" icon="info-outline" />
				}
			/>
		</div>
	);
};

export { CheckboxFee, CheckboxFeeWithTooltip };
