import { __ } from '@wordpress/i18n';
import * as React from 'react';
import { Fee } from '../../types/Fee';
import { FeesData } from '../../types/Ticket';
import AddFee from './AddFee';
import CheckboxFee from './CheckboxFee';
import CheckboxFeeWithTooltip from './CheckboxFeeWithTooltip';
import SelectFee from './SelectFee';

type TicketFeesProps = {
	fees: FeesData;
	onFeesChange?: ( fees: FeesData ) => void;
};

export default function TicketFees( props: TicketFeesProps ): React.JSX.Element {
	const { fees, onFeesChange } = props;

	// Extract fees from props
	const { availableFees, automaticFees, selectedFees } = fees;

	// State for displayed fees and UI interactions
	const [ displayedFees, setDisplayedFees ] = React.useState< Fee[] >( [] );
	const [ isSelectingFee, setIsSelectingFee ] = React.useState< boolean >( false );

	// Create a map of selected fees for easy lookup
	const feeIdSelectedMap: Record< number, boolean > = {};
	availableFees.forEach( ( fee ) => {
		feeIdSelectedMap[ fee.id ] = selectedFees.some( ( selectedFee ) => selectedFee.id === fee.id );
	} );

	const hasAutomaticFees = automaticFees.length > 0;
	const hasDisplayedFees = displayedFees.length > 0;
	const hasItemsToDisplay = hasAutomaticFees || hasDisplayedFees;

	// Handle fee selection changes
	const onSelectedFeesChange = React.useCallback(
		( event: React.ChangeEvent< HTMLInputElement > ) => {
			const feeId = parseInt( event.target.value, 10 );
			const isChecked = ! feeIdSelectedMap[ feeId ];

			if ( onFeesChange ) {
				const updatedSelectedFees = isChecked
					? [ ...selectedFees, availableFees.find( ( f ) => f.id === feeId )! ]
					: selectedFees.filter( ( fee ) => fee.id !== feeId );

				onFeesChange( {
					...fees,
					selectedFees: updatedSelectedFees,
				} );
			}

			// Add to displayed fees if not already there
			if ( isChecked ) {
				const fee = availableFees.find( ( f ) => f.id === feeId );
				if ( fee && ! displayedFees.find( ( f ) => f.id === feeId ) ) {
					setDisplayedFees( ( prev ) => [ ...prev, fee ] );
				}
			}
		},
		[ feeIdSelectedMap, selectedFees, availableFees, fees, onFeesChange, displayedFees ]
	);

	// Handle add fee button click
	const onAddFeeClick = React.useCallback( () => {
		setIsSelectingFee( true );
	}, [] );

	// Handle fee selection cancel
	const onCancelFeeSelect = React.useCallback( () => {
		setIsSelectingFee( false );
	}, [] );

	// Handle fee selection confirm
	const onConfirmFeeSelect = React.useCallback(
		( feeId: number ) => {
			const fee = availableFees.find( ( f ) => f.id === feeId );
			if ( fee && ! displayedFees.find( ( f ) => f.id === feeId ) ) {
				setDisplayedFees( ( prev ) => [ ...prev, fee ] );
			}

			if ( onFeesChange ) {
				const updatedSelectedFees = [ ...selectedFees, fee! ];
				onFeesChange( {
					...fees,
					selectedFees: updatedSelectedFees,
				} );
			}

			setIsSelectingFee( false );
		},
		[ availableFees, displayedFees, selectedFees, fees, onFeesChange ]
	);

	// Get selectable fees (fees not already displayed)
	const selectableFees = availableFees.filter(
		( fee ) => ! displayedFees.find( ( displayedFee ) => displayedFee.id === fee.id )
	);

	// Tooltip text for automatic fees
	const tooltipText = __( 'This fee is automatically added to the ticket.', 'event-tickets' );

	return (
		<div className="classy-field classy-field__fees">
			<div className="classy-field__label">{ __( 'Ticket Fees', 'event-tickets' ) }</div>
			<div className="classy-field__fees-container">
				{ hasAutomaticFees && (
					<div className="classy-field__automatic-fees">
						{ automaticFees.map( ( fee ) => (
							<CheckboxFeeWithTooltip
								key={ fee.id }
								fee={ fee }
								isChecked={ true }
								isDisabled={ true }
								onChange={ () => {} }
								tooltipText={ tooltipText }
							/>
						) ) }
					</div>
				) }

				{ hasDisplayedFees && (
					<div className="classy-field__displayed-fees">
						{ displayedFees.map( ( fee ) => (
							<CheckboxFee
								key={ fee.id }
								fee={ fee }
								isChecked={ feeIdSelectedMap[ fee.id ] || false }
								isDisabled={ false }
								onChange={ onSelectedFeesChange }
							/>
						) ) }
					</div>
				) }

				{ isSelectingFee ? (
					<SelectFee
						availableFees={ selectableFees }
						onCancel={ onCancelFeeSelect }
						onConfirm={ onConfirmFeeSelect }
					/>
				) : (
					<AddFee onClick={ onAddFeeClick } />
				) }

				{ ! hasItemsToDisplay && (
					<p className="classy-field__no-fees">{ __( 'No available fees.', 'event-tickets' ) }</p>
				) }
			</div>
		</div>
	);
}
