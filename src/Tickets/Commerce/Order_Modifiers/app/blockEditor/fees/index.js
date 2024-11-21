/**
 * External dependencies
 */
import classNames from 'classnames';
import { LabeledItem, } from '@moderntribe/common/elements';
import { useSelect, useDispatch, } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { setTicketHasChangesInCommonStore } from '../store/common-store-bridge';
import { mapFeeToItem } from './map-fee-object';
import AddFee from './add-fee';
import SelectFee from './select-fee';
import './style.pcss';

// The name of the store for fees.
const storeName = 'tec-tickets-fees';

/**
 * The fees section component for the ticket editor.
 *
 * The fees section needs logic to handle the selection of fees. The default
 * view is to show the "+ Add fee" button. Any previously-selected fees should
 * be displayed as checked above the button.
 *
 * When the "+ Add fee" button is clicked, the user should be able to select
 * from a list of available fees. They can then confirm the selection with the
 * "Add fee" button, or cancel with the "Cancel" button.
 *
 * The selected fees should be displayed as checked above the "+ Add fee" button.
 * Once a fee has been displayed, it should not be hidden even if it is un-checked
 * again. It should only be hidden after it is unchecked AND the page has been
 * reloaded.
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
	const displayedFees = [];

	// Initialize the selected fees map with the available fees.
	feesAvailable.forEach( ( fee ) => {
		feeIdSelectedMap[ fee.id ] = false;
	} );

	// Set the selected fees to true.
	feesSelected.forEach( ( feeId ) => {
		feeIdSelectedMap[ feeId ] = true;
		let fee = feesAvailable.find( ( fee ) => fee.id === feeId );
		if ( fee ) {
			displayedFees.push( fee );
		}
	} );

	// Set up the state for the selected fees.
	const [ checkedFees, setCheckedFees ] = useState( feeIdSelectedMap );

	// Set up the dispatch functions for adding and removing fees.
	const { addFeeToTicket, removeFeeFromTicket } = useDispatch( storeName );

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

			setTicketHasChangesInCommonStore( clientId );
		},
		[ clientId, checkedFees ]
	);

	// Set up the state for the fee selection.
	const [ isSelectingFee, setIsSelectingFee ] = useState( false );
	const onAddFeeClick = useCallback(
		() => {
			setIsSelectingFee( true );
		},
		[ clientId ]
	);

	// Set up the functions for the fee selection.
	const onCancelFeeSelect = useCallback(
		() => {
			setIsSelectingFee( false );
		},
		[ clientId ]
	);

	const onConfirmFeeSelect = useCallback(
		( feeId ) => {
			// We're done selecting a fee.
			setIsSelectingFee( false );

			// Update the list of checked fees.
			setCheckedFees( {
				...checkedFees,
				[ feeId ]: true,
			} );

			// Dispatch the action to add the fee to the ticket.
			addFeeToTicket( clientId, feeId );
		},
		[ clientId, checkedFees, feesAvailable ]
	)

	// Set up the fees that are available to be selected.
	const selectableFees = feesAvailable.filter( ( fee ) => {
		// The fee should not be selectable if it's already in the display fees.
		return ! displayedFees.some( ( displayedFee ) => displayedFee.id === fee.id );
	} );

	return (
		<div
			className={ classNames(
				'tribe-editor__ticket__fees',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--fees'
			) }
		>
			<LabeledItem
				className={ classNames(
					'tribe-editor__labeled-item',
					'tribe-editor__ticket__active-fees-label'
				) }
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

				{ displayedFees.length > 0 ? (
					displayedFees.map( ( fee ) => mapFeeToItem( {
						isDisabled: false,
						onChange: onSelectedFeesChange,
						isChecked: checkedFees[ fee.id ],
						fee: fee,
						clientId: clientId,
					} ) )
				) : null }

				{ isSelectingFee
					? <SelectFee
						feesAvailable={ selectableFees }
						onCancel={ onCancelFeeSelect }
						onConfirm={ onConfirmFeeSelect }
					/>
					: <AddFee onClick={ onAddFeeClick }/> }

				{ ! hasItemsToDisplay ? (
					<p>{ __( 'No available fees.', 'event-tickets' ) }</p>
				) : null }
			</div>
		</div>
	);
}

export default FeesSection;
