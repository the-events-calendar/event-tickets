/**
 * External dependencies.
 */
import classNames from 'classnames';
import { Button, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { mapFeeToOption } from './map-fee-object';

/**
 *
 * @param {Fee[]} feesAvailable
 * @param {function} onChange
 */
const SelectFee = ( { feesAvailable, onConfirm, onCancel } ) => {
	// Set up options for the select control.
	const options = [
		{
			label: __( 'Select a fee', 'event-tickets' ),
			value: '',
		}
	];

	feesAvailable.forEach( ( fee ) => {
		options.push( mapFeeToOption( fee ) );
	} );

	// Set up the state for the selected fee and the add fee button.
	const [ selectedFee, setSelectedFee ] = useState( '' );
	const [ addButtonDisabled, setAddButtonDisabled ] = useState( true );

	// Handle the selection change.
	const onSelectionChange = ( value ) => {
		setSelectedFee( value );
		setAddButtonDisabled( value === '' );
	}

	return (
		<div className="tec-events-block-editor__fee-select-container">
			<SelectControl
				className={ classNames(
					'tec-events-block-editor__fee-select',
				) }
				hideLabelFromVision
				options={ options }
				onChange={ onSelectionChange }
				value={ selectedFee }
			/>
			<Button
				className={ classNames(
					'tec-events-block-editor__fee-select__add-fee'
				) }
				disabled={ addButtonDisabled }
				variant="secondary"
				label={ _x(
					'Confirm adding selected fee to the ticket',
					'aria-label for confirming adding selected fee to ticket',
					'event-tickets'
				) }
				onClick={ () => onConfirm( Number.parseInt( selectedFee ) ) }
			>
				{ __( 'Add fee', 'event-tickets' ) }
			</Button>
			<Button
				className={ classNames(
					'tec-events-block-editor__fee-select__cancel'
				) }
				variant="tertiary"
				label={ _x(
					'Cancel adding fee to the ticket',
					'aria-label for canceling adding fee to ticket',
					'event-tickets'
				) }
				onClick={ onCancel }
			>
				{ __( 'Cancel', 'event-tickets' ) }
			</Button>
		</div>
	)
};

export default SelectFee;
