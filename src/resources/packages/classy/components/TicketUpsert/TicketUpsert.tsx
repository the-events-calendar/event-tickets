import { CenteredSpinner, IconNew, LabeledInput } from '@tec/common/classy/components';
import { __experimentalInputControl as InputControl, Button, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { Fragment, useCallback, useState } from 'react';
import { Capacity, SaleDuration, SalePrice, TicketDescription, TicketName, } from '../../fields';
import { CoreEditorSelect } from '../../types/Store';
import { Capacity as CapacityType, PartialTicket, SalePriceDetails } from '../../types/Ticket';
import { CurrencyInput } from '../CurrencyInput';
import * as TicketApi from '../../api/tickets';

type TicketUpsertProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onSave: ( data: PartialTicket ) => void;
	value: PartialTicket;
}

const defaultValues: PartialTicket = {
	title: '',
	description: '',
	cost: '',
	salePriceData: {
		enabled: false,
		salePrice: '',
		startDate: null,
		endDate: null,
	}
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
		value,
	} = props;

	const { eventId } = useSelect( ( select: SelectFunction ) => {
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );
		return {
			eventId: getCurrentPostId(),
		};
	}, [] );

	const [ currentValues, setCurrentValues ] = useState<PartialTicket>( {
		eventId: eventId,
		...defaultValues,
		...value,
	} );

	// Tickets must have a name at a minimum.
	const [ confirmEnabled, setConfirmEnabled ] = useState<boolean>( currentValues.title !== '' );
	const [ ticketUpsertError, setTicketUpsertError ] = useState<Error | null>( null );
	const [ saveInProgress, setSaveInProgress ] = useState<boolean>( false );

	const invokeSaveWithData: () => void = useCallback( (): void => {
		// Clear any previous error.
		setTicketUpsertError( null );

		// If the ticket name is empty, we cannot save.
		if ( ! confirmEnabled ) {
			setTicketUpsertError( new Error( _x( 'Please enter a ticket name.', 'Error message for missing ticket name', 'event-tickets' ) ) );
			return;
		}

		setSaveInProgress( true );

		// todo: better data mapping for the ticket data.
		const dataToSave: PartialTicket = {
			title: currentValues.title,
			description: currentValues.description,
			cost: currentValues.cost,
			salePriceData: currentValues.salePriceData,
		};

		TicketApi.upsertTicket( currentValues )
			.then( ( ticket: PartialTicket ) => {
				setSaveInProgress( false );
				setTicketUpsertError( null );
				onSave( ticket );
			} )
			.catch( ( error: Error ) => {
				setSaveInProgress( false );
				setTicketUpsertError( error );
			} );
	}, [ confirmEnabled, currentValues ] );

	return (
		<div className="classy-root">
			<header className="classy-modal__header classy-modal__header--ticket">
				<IconNew/>
				<h4 className="classy-modal__header-title">
					{ isUpdate
						? _x( 'Edit Ticket', 'Update ticket modal header title', 'event-tickets' )
						: _x( 'New Ticket', 'Create ticket modal header title', 'event-tickets' ) }
				</h4>
			</header>

			<hr className="classy-modal__section-separator"></hr>

			{ /* todo: this should highlight any errors in the form, maybe instead of a message */}
			{ ticketUpsertError && (
				<Fragment>
					<div className="classy-modal__error">
						<p>{ ticketUpsertError.message }</p>
					</div>
					<hr className="classy-modal__section-separator"></hr>
				</Fragment>
			) }

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<TicketName
					value={ decodeEntities( currentValues.title ) }
					onChange={ ( value: string ) => {
						const newValue = value || '';
						setConfirmEnabled( newValue !== '' );
						return setCurrentValues( { ...currentValues, title: newValue } );
					} }
				/>

				<TicketDescription
					value={ decodeEntities( currentValues.description ) }
					onChange={ ( value: string ) => {
						return setCurrentValues( { ...currentValues, description: value || '' } );
					} }
				/>

				<CurrencyInput
					label={ _x( 'Ticket Price', 'Label for the ticket price field', 'event-tickets' ) }
					value={ decodeEntities( currentValues.cost ) }
					onChange={ ( value: string ) => {
						return setCurrentValues( { ...currentValues, cost: value || '' } );
					} }
				/>

				<SalePrice
					value={ currentValues.salePriceData as SalePriceDetails }
					onChange={ ( value: SalePriceDetails ) => {
						return setCurrentValues( { ...currentValues, salePriceData: value } );
					} }
				/>
			</section>

			<hr className="classy-modal__section-separator"/>

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
						label={ _x(
							'Share capacity with other tickets',
							'Label for sharing capacity toggle',
							'event-tickets'
						) }
						__nextHasNoMarginBottom={ true }
						checked={ currentValues.capacityShared }
						onChange={ ( value: boolean ) => {
							setCurrentValues( { ...currentValues, capacityShared: value } );
						} }
					/>
				</div>
			</section>

			<hr className="classy-modal__section-separator"/>

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<div className="classy-field__input-title">
					{ _x(
						'Sale Duration',
						'Title for the sale duration section in the Classy editor',
						'event-tickets'
					) }
				</div>
				<SaleDuration

				/>
			</section>

			<footer className="classy-modal__footer classy-modal__footer--ticket">
				<div className="classy-modal__actions classy-modal__actions--ticket">
					<Button
						aria-disabled={ ! confirmEnabled || saveInProgress }
						className="classy-button"
						onClick={ invokeSaveWithData }
						variant="primary"
					>
						{ saveInProgress && (
							<CenteredSpinner />
						) }

						{
							isUpdate
								? _x( 'Update Ticket', 'Update ticket button label', 'event-tickets' )
								: _x( 'Create Ticket', 'Create ticket button label', 'event-tickets' )
						}
					</Button>
					<Button
						aria-disabled={ saveInProgress }
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
