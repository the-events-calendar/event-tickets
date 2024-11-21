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
import FeeSelect from './select-fee';
import './style.pcss';

// The name of the store for fees.
const storeName = 'tec-tickets-fees';

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

	/*
	 * The fees section needs logic to handle the selection of fees. The default
	 * view is to show the "+ Add fee" button. Any selected fees should be displayed
	 * as checked above the button.
	 *
	 * When the "+ Add fee" button is clicked, the user should be able to select
	 * from a list of available fees.
	 */

	// Set up the state for the fee selection.
	const [ isSelectingFee, setIsSelectingFee ] = useState( false );
	const onAddFeeClick = useCallback( () => { setIsSelectingFee( true ); } );

	// Set up the functions for the fee selection.
	const onCancelFeeSelect = useCallback( () => { setIsSelectingFee( false ); } );
	const onConfirmFeeSelect = useCallback(
		( feeId ) => {
			setIsSelectingFee( false );
			setCheckedFees( {
				...checkedFees,
				[ feeId ]: true,
			} );
		}
	)

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

				{ isSelectingFee
					? <FeeSelect
						feesAvailable={ feesAvailable}
						onCancel={ onCancelFeeSelect }
						onConfirm={ onConfirmFeeSelect }
					/>
					: <AddFee onClick={ onAddFeeClick }/>
				}

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
