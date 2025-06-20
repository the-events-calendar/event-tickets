import * as React from 'react';
import { useCallback, useState } from 'react';
import { _x } from '@wordpress/i18n';
import {
	__experimentalInputControl as InputControl,
	Button,
	CustomSelectControl,
	ToggleControl
} from '@wordpress/components';
import { CenteredSpinner, IconNew, LabeledInput } from '@tec/common/classy/components';
import { CustomSelectOption } from '@wordpress/components/build-types/custom-select-control/types';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { decodeEntities } from '@wordpress/html-entities';
import { Capacity as CapacityType } from '../../types/Capacity';
import { Ticket } from '../../types/Ticket';
import {
	Capacity,
	SalePrice,
	TicketName,
	TicketDescription,
} from '../../fields';
import { CurrencyInput } from '../CurrencyInput';

type TicketUpsertProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onSave: ( data: Ticket ) => void;
	values: Ticket;
}

const defaultValues: Ticket = {
	name: '',
	description: '',
	price: '',
	hasSalePrice: false,
	salePrice: '',
	capacityType: 'general-admission',
	capacity: '',
	capacityShared: false,
	selectedFees: [],
	displayedFees: [],
};

/**
 * TicketUpsert component for creating or updating tickets.
 *
 * @param {TicketUpsertProps} props
 * @return {JSX.Element} The rendered ticket upsert component.
 */
export default function TicketUpsert( props: TicketUpsertProps ): JSX.Element {

	const {
		isUpdate,
		onCancel,
		onSave,
		values,
	} = props;

	const [ currentValues, setCurrentValues ] = useState<Ticket>( {
		...defaultValues,
		...values,
	} );

	// Tickets must have a name at a minimum.
	const [ confirmEnabled, setConfirmEnabled ] = useState<boolean>( currentValues.name !== '' );

	const invokeSaveWithData: () => void = useCallback( (): void => {
		if ( ! confirmEnabled ) {
			return;
		}

		const dataToSave: Ticket = {
			name: currentValues.name,
			description: currentValues.description,
			price: currentValues.price,
			hasSalePrice: currentValues.hasSalePrice,
			salePrice: currentValues.salePrice,
			capacityType: currentValues.capacityType,
			capacity: currentValues.capacity,
			capacityShared: currentValues.capacityShared,
			selectedFees: currentValues.selectedFees,
			displayedFees: currentValues.displayedFees,
		};

		onSave( dataToSave );
	}, [ currentValues ] );


	return (
		<div className="classy-root">
			<header className="classy-modal__header classy-modal__header--ticket">
				<IconNew />
				<h4 className="classy-modal__header-title">
					{ isUpdate
						? _x( 'Edit Ticket', 'Update ticket modal header title', 'event-tickets' )
						: _x( 'New Ticket', 'Create ticket modal header title', 'event-tickets' ) }
				</h4>
			</header>

			<hr className="classy-modal__section-separator"></hr>

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<TicketName
					value={ decodeEntities( currentValues.name ) }
					onChange={ ( value: string ) => {
						const newValue = value || '';
						setCurrentValues( { ...currentValues, name: newValue } );
						setConfirmEnabled( newValue !== '' );
					} }
				/>

				<TicketDescription
					value={ decodeEntities( currentValues.description ) }
					onChange={ ( value: string ) => {
						setCurrentValues( { ...currentValues, description: value || '' } );
					} }
				/>

				<CurrencyInput
					label={ _x( 'Ticket Price', 'Label for the ticket price field', 'event-tickets' ) }
					value={ decodeEntities( currentValues.price ) }
					onChange={ ( value: string ) => {
						setCurrentValues( { ...currentValues, price: value || '' } );
					} }
				/>

				<SalePrice

				/>
			</section>

			<hr className="classy-modal__section-separator" />

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<div className="classy-field__input-title">
					{ _x( 'Capacity', 'Title for the capacity section in the Classy editor', 'event-tickets' ) }
				</div>

				<div className="classy-field__capacity">
					<Capacity
						value={ currentValues.capacityType }
						onChange={ ( value: string ) => {
							setCurrentValues( { ...currentValues, capacityType: value as CapacityType } );
						} }
					/>

					<LabeledInput
						label={ _x( 'Ticket Capacity', 'Label for the ticket capacity field', 'event-tickets' ) }
					>
						<InputControl
							className="classy-field__control classy-field__control--input classy-field__control--input-narrow"
							label={ _x( 'Ticket Capacity', 'Label for the ticket capacity field', 'event-tickets' ) }
							hideLabelFromVision={ true }
							value={ String( currentValues.capacity || '' ) }
							onChange={ ( value: string ) => {
								const capacityValue = value ? parseInt( value, 10 ) : undefined;
								setCurrentValues( { ...currentValues, capacity: capacityValue } );
							} }
							size="small"
							__next40pxDefaultSize={ true }
						/>
						<div className="classy-field__input-note">
							{ _x( 'Leave blank for unlimited', 'Ticket capacity input note', 'event-tickets' ) }
						</div>
					</LabeledInput>

					<ToggleControl
						label={ _x( 'Share capacity with other tickets', 'Label for sharing capacity toggle', 'event-tickets' ) }
						__nextHasNoMarginBottom={ true }
						checked={ currentValues.capacityShared }
						onChange={ ( value: boolean ) => {
							setCurrentValues( { ...currentValues, capacityShared: value } );
						} }
					/>
				</div>
			</section>

			<footer className="classy-modal__footer classy-modal__footer--ticket">
				<div className="classy-modal__actions classy-modal__actions--ticket">
					<Button
						aria-disabled={ ! confirmEnabled }
						className="classy-button"
						onClick={ invokeSaveWithData }
						variant="primary"
					>
						{
							isUpdate
								? _x( 'Update Ticket', 'Update ticket button label', 'event-tickets' )
								: _x( 'Create Ticket', 'Create ticket button label', 'event-tickets' )
						}
					</Button>
					<Button
						className="classy-button"
						onClick={ onCancel }
						variant="link"
					>
						{ _x( 'Cancel', 'Cancel button label', 'event-tickets' ) }
					</Button>
				</div>
			</footer>
		</div>
	);
}
