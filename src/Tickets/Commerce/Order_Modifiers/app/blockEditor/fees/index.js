/**
 * External dependencies
 */
import classNames from 'classnames';
import { Checkbox, LabeledItem, } from '@moderntribe/common/elements';
import { useSelect, useDispatch, } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// The name of the store for fees.
const storeName = 'tec-tickets-fees';

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
 * Maps a fee to a checkbox item.
 *
 * @param {Fee} fee The fee object to map.
 * @param {boolean} isDisabled Whether the fee is disabled.
 * @param {function} onChange The change handler for the fee.
 * @param {boolean} isChecked Whether the fee is checked.
 * @param {string} clientId The client ID of the ticket.
 * @return {JSX.Element|null} The checkbox item, or null if the fee is not active.
 */
const mapFeeToItem = ( { fee, isDisabled, onChange, isChecked, clientId } ) => {
	// We shouldn't have these here, but just in case skip anything not active.
	if ( fee.status !== 'active' ) {
		return null;
	}

	// Todo: the precision should be determined by settings.
	const amount = Number.parseFloat( fee.raw_amount ).toFixed( 2 );

	let feeLabel;
	if ( fee.sub_type === 'percent' ) {
		feeLabel = `${ fee.display_name } (${ amount }%)`;
	} else {
		feeLabel = `${ fee.display_name } ($${ amount })`;
	}

	const classes = [ 'tribe-editor__ticket__fee-checkbox' ];
	const name = `tec-ticket-fee-${ fee.id }-${ clientId }`;

	return (
		<Checkbox
			checked={ isChecked }
			className={ classNames( classes ) }
			disabled={ isDisabled }
			id={ name }
			label={ feeLabel }
			onChange={ onChange }
			name={ name }
			value={ fee.id }
			key={ fee.id }
		/>
	);
};

/**
 * The fees section component for the ticket editor.
 *
 * @since TBD
 *
 * @param {string} props.clientId The client ID of the ticket.
 * @return {JSX.Element}
 * @constructor
 */
function FeesSection( props ) {
	// Extract the clientId from the props.
	const { clientId } = props;

	// Get the available and automatic fees from the store.
	const {
		feesAvailable,
		feesAutomatic,
	} = useSelect(
		( select ) => {
			return {
				feesAvailable: select( storeName ).getAvailableFees(),
				feesAutomatic: select( storeName ).getAutomaticFees(),
			};
		},
		[]
	);

	const hasAutomaticFees = feesAutomatic.length > 0;
	const hasAvailableFees = feesAvailable.length > 0;
	const hasItemsToDisplay = hasAutomaticFees || hasAvailableFees;

	// Set up the state for the selected fees.
	const feesSelected = useSelect(
		( select ) => select( storeName ).getSelectedFees( clientId ),
		[ clientId ]
	);

	const feeIdSelectedMap = {};

	// Initialize the selected fees map with the available fees.
	feesAvailable.forEach( ( fee ) => {
		feeIdSelectedMap[ fee.id ] = false;
	} );

	// Set the selected fees to true.
	feesSelected.forEach( ( feeId ) => {
		feeIdSelectedMap[ feeId ] = true;
	} );

	const [ checkedFees, setCheckedFees ] = useState( feeIdSelectedMap );
	const { addFeeToTicket, removeFeeFromTicket } = useDispatch( storeName );

	console.log( checkedFees );

	/**
	 * Handles the change event for the selected fees.
	 *
	 * @param {event} event The change event.
	 */
	const onSelectedFeesChange = useCallback(
		( event ) => {
			const feeId = Number.parseInt( event.target.value );
			const isChecked = ! checkedFees[ feeId ];

			if ( isChecked ) {
				addFeeToTicket( clientId, feeId );
			} else {
				removeFeeFromTicket( clientId, feeId );
			}

			setCheckedFees( {
				...checkedFees,
				[ feeId ]: isChecked,
			} );

		},
		[ clientId, checkedFees ]
	);

	return (
		<div
			className={ classNames(
				'tribe-editor__ticket__fees',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--fees'
			) }
		>
			<LabeledItem
				className="tribe-editor__ticket__active-fees-label"
				label={ __( 'Ticket Fees', 'event-tickets' ) }
			/>

			<div className="tribe-editor__ticket__order_modifier_fees">
				{ hasAutomaticFees ? (
					feesAutomatic.map( ( fee ) => mapFeeToItem( {
						isDisabled: true,
						isChecked: true,
						fee: fee,
						clientId: clientId,
					} ) )
				) : null }

				{ hasAvailableFees ? (
					feesAvailable.map( ( fee ) => mapFeeToItem( {
						isDisabled: false,
						onChange: onSelectedFeesChange,
						isChecked: checkedFees[fee.id],
						fee: fee,
						clientId: clientId,
					} ) )
				) : null }

				{ ! hasItemsToDisplay ? (
					<p>{ __( 'No available fees.', 'event-tickets' ) }</p>
				) : null }
			</div>
		</div>
	);
}

export default FeesSection;
