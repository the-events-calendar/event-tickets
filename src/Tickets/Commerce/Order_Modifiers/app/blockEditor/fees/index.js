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
import AddFee from './add-fee';
import { CheckboxFee, CheckboxFeeWithTooltip } from './checkbox-fee';
import SelectFee from './select-fee';
import './style.pcss';

const { storeName } = require( '../store' );

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
 * @since 5.18.0
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
			return select( storeName ).getAllFees();
		},
		[]
	);

	// Set up the state for the selected fees.
	const { feesSelected, feesDisplayed } = useSelect(
		( select ) => {
			return {
				feesSelected: select( storeName ).getSelectedFees( clientId ),
				feesDisplayed: select( storeName ).getDisplayedFees( clientId ),
			}
		},
		[ clientId ]
	);

	const hasAutomaticFees = feesAutomatic.length > 0;
	const hasDisplayedFees = feesDisplayed.length > 0;
	const hasItemsToDisplay = hasAutomaticFees || hasDisplayedFees;

	const feeIdSelectedMap = {};

	// Initialize the selected fees map with the available fees.
	feesAvailable.forEach( ( fee ) => {
		feeIdSelectedMap[ fee.id ] = false;
	} );

	// Set the selected fees to true.
	feesSelected.forEach( ( feeId ) => {
		feeIdSelectedMap[ feeId ] = true;
	} );

	// Set up the state for the selected fees.
	const [ checkedFees, setCheckedFees ] = useState( feeIdSelectedMap );

	// Set up the dispatch functions for working with the data store.
	const {
		addFeeToTicket,
		removeFeeFromTicket,
		addDisplayedFee,
	} = useDispatch( storeName );

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

			// Dispatch the necessary actions to the store.
			addFeeToTicket( clientId, feeId );
			addDisplayedFee( clientId, feeId );
		},
		[ clientId, checkedFees ]
	)

	// Set up the fees that are available to be selected.
	const selectableFees = feesAvailable.filter( ( fee ) => {
		// The fee should not be selectable if it's already in the display fees.
		return ! feesDisplayed.some( ( displayedFee ) => displayedFee.id === fee.id );
	} );

	// Tooltip text for automatic fees.
	const toolTipText = __( 'This fee is automatically added to the ticket.', 'event-tickets' );

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
					feesAutomatic.map(
						( fee ) => (
							<CheckboxFeeWithTooltip
								clientId={ clientId }
								fee={ fee }
								isChecked={ true }
								isDisabled={ true }
								onChange={ () => {} }
								tooltipText={ toolTipText }
							/>
						) )
				) : null }

				{ hasDisplayedFees ? (
					feesDisplayed.map( ( fee ) => (
						<CheckboxFee
							isDisabled={ false }
							onChange={ onSelectedFeesChange }
							isChecked={ checkedFees[ fee.id ] }
							fee={ fee }
							clientId={ clientId }
						/>
					) )
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
