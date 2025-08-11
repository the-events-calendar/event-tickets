import { IconNew } from '@tec/common/classy/components';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { Fragment, useCallback, useState } from 'react';
import { Capacity, SaleDuration, SalePrice, TicketDescription, TicketName } from '../../fields';
import { CoreEditorSelect } from '../../types/Store';
import { CapacitySettings, SalePriceDetails, TicketId, TicketSettings } from '../../types/Ticket';
import { CurrencyInput } from '../CurrencyInput';
import * as TicketApi from '../../api/tickets';

type TicketUpsertProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onDelete?: ( ticketId: TicketId ) => void;
	onSave: ( data: TicketSettings ) => void;
	value: TicketSettings;
};

const defaultValues: TicketSettings = {
	id: 0,
	name: '',
	description: '',
	cost: '',
	salePriceData: {
		enabled: false,
		salePrice: '',
		startDate: null,
		endDate: null,
	},
	capacitySettings: {
		enteredCapacity: '',
		isShared: false,
	},
	costDetails: {
		currencySymbol: '$',
		currencyPosition: 'prefix',
		currencyDecimalSeparator: '.',
		currencyThousandSeparator: ',',
		suffix: '',
		values: [],
	},
	fees: {
		availableFees: [],
		automaticFees: [],
		selectedFees: [],
	},
};

const createButtonLabel = _x( 'Create Ticket', 'Create ticket button label', 'event-tickets' );
const updateButtonLabel = _x( 'Update Ticket', 'Update ticket button label', 'event-tickets' );
const deleteButtonLabel = _x( 'Delete Ticket', 'Delete ticket button label', 'event-tickets' );
const cancelButtonLabel = _x( 'Cancel', 'Cancel button label', 'event-tickets' );
const noop = () => {};

/**
 * TicketUpsert component for creating or updating tickets.
 *
 * @param {TicketUpsertProps} props
 * @return {JSX.Element} The rendered ticket upsert component.
 */
export default function TicketUpsert( props: TicketUpsertProps ): JSX.Element {
	const { isUpdate, onCancel, onDelete = noop, onSave, value } = props;

	const { eventId } = useSelect( ( select: SelectFunction ) => {
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );
		return {
			eventId: getCurrentPostId(),
		};
	}, [] );

	const [ currentValues, setCurrentValues ] = useState< TicketSettings >( {
		eventId: eventId,
		...defaultValues,
		...value,
	} );

	const [ costValue, setCostValue ] = useState< number >(
		currentValues.costDetails.values.length > 0 ? currentValues.costDetails.values[ 0 ] : 0
	);

	// Tickets must have a name at a minimum.
	const [ confirmEnabled, setConfirmEnabled ] = useState< boolean >( currentValues.name !== '' );
	const [ ticketUpsertError, setTicketUpsertError ] = useState< Error | null >( null );
	const [ saveInProgress, setSaveInProgress ] = useState< boolean >( false );

	const onValueChange = ( key: string, newValue: any ): void => {
		return setCurrentValues( {
			...currentValues,
			[ key ]: newValue,
		} );
	};

	const invokeSaveWithData: () => void = useCallback( (): void => {
		// Clear any previous error.
		setTicketUpsertError( null );

		// If the ticket name is empty, we cannot save.
		if ( ! confirmEnabled ) {
			setTicketUpsertError(
				new Error(
					_x( 'Please enter a ticket name.', 'Error message for missing ticket name', 'event-tickets' )
				)
			);
			return;
		}

		setSaveInProgress( true );

		// todo: better data mapping for the ticket data.

		TicketApi.upsertTicket( currentValues )
			.then( ( ticket: TicketSettings ) => {
				setSaveInProgress( false );
				setTicketUpsertError( null );

				// Use the returned ticket data to update the current values.
				setCurrentValues( ticket );
				onSave( ticket );
			} )
			.catch( ( error: Error ) => {
				setSaveInProgress( false );
				setTicketUpsertError( error );
			} );
	}, [ confirmEnabled, currentValues ] );

	const onDeleteClicked = useCallback( (): void => {
		TicketApi.deleteTicket( currentValues.id )
			.then( () => {
				setSaveInProgress( false );
				setTicketUpsertError( null );
				onDelete( currentValues.id );
			} )
			.catch( ( error: Error ) => {
				setSaveInProgress( false );
				setTicketUpsertError( error );
			} );
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

			{ /* todo: this should highlight any errors in the form, instead of showing a message */ }
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
					value={ decodeEntities( currentValues.name ) }
					onChange={ ( value: string ) => {
						const newValue = value || '';
						setConfirmEnabled( newValue !== '' );
						return onValueChange( 'name', newValue );
					} }
				/>

				<TicketDescription
					value={ decodeEntities( currentValues.description ) }
					onChange={ ( value: string ) => onValueChange( 'description', value || '' ) }
				/>

				<CurrencyInput
					label={ _x( 'Ticket Price', 'Label for the ticket price field', 'event-tickets' ) }
					value={ costValue > 0 ? costValue.toString() : '' }
					onChange={ ( value: string ) => {
						const numericValue = parseFloat( value );
						if ( isNaN( numericValue ) ) {
							setCostValue( 0 );
							return onValueChange( 'costDetails', {
								values: [],
								...currentValues.costDetails,
							} );
						}

						setCostValue( numericValue );
						return onValueChange( 'costDetails', {
							values: [ numericValue ],
							...currentValues.costDetails,
						} );
					} }
				/>

				<SalePrice
					value={ currentValues.salePriceData as SalePriceDetails }
					onChange={ ( value: SalePriceDetails ) => onValueChange( 'salePriceData', value ) }
				/>
			</section>

			<hr className="classy-modal__section-separator" />

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<div className="classy-field__input-title">
					{ _x( 'Capacity', 'Title for the capacity section in the Classy editor', 'event-tickets' ) }
				</div>

				<div className="classy-field__capacity">
					<Capacity
						value={ currentValues.capacitySettings }
						onChange={ ( value: CapacitySettings ) =>
							onValueChange( 'capacitySettings', value as CapacitySettings )
						}
					/>
				</div>
			</section>

			<hr className="classy-modal__section-separator" />

			<section className="classy-modal__content classy-modal__content--ticket classy-field__inputs classy-field__inputs--unboxed">
				<div className="classy-field__input-title">
					{ _x(
						'Sale Duration',
						'Title for the sale duration section in the Classy editor',
						'event-tickets'
					) }
				</div>
				<SaleDuration />
			</section>

			<footer className="classy-modal__footer classy-modal__footer--ticket">
				<div className="classy-modal__actions classy-modal__actions--ticket">
					<Button
						aria-disabled={ ! confirmEnabled }
						isBusy={ saveInProgress }
						className="classy-button"
						onClick={ invokeSaveWithData }
						variant="primary"
					>
						{ isUpdate ? updateButtonLabel : createButtonLabel }
					</Button>

					<Button
						aria-disabled={ saveInProgress }
						isBusy={ saveInProgress }
						className="classy-button"
						onClick={ onCancel }
						variant="link"
					>
						{ cancelButtonLabel }
					</Button>

					{ isUpdate && (
						<Button
							aria-disabled={ saveInProgress }
							isBusy={ saveInProgress }
							className="classy-button classy-button__destructive"
							onClick={ onDeleteClicked }
							variant="link"
						>
							{ deleteButtonLabel }
						</Button>
					) }
				</div>
			</footer>
		</div>
	);
}
