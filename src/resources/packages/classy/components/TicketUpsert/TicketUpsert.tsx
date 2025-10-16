import * as React from 'react';
import {
	ClassyModalActions as Actions,
	ClassyModalFooter as Footer,
	ClassyModalRoot as Root,
	ClassyModalSection,
	IconNew,
} from '@tec/common/classy/components';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import { Fragment, useCallback, useState } from 'react';
import { Capacity, SaleDuration, SalePrice, TicketDescription, TicketName } from '../../fields';
import { CapacitySettings, SalePriceDetails, TicketId, TicketSettings } from '../../types/Ticket';
import { CurrencyInput } from '../CurrencyInput';
import * as TicketApi from '../../api/tickets';
import { getCurrencySettings } from '../../localizedData';
import { CoreEditorSelect } from '@tec/common/classy/types/Store';

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
		startDate: '',
		endDate: '',
	},
	capacitySettings: {
		enteredCapacity: '',
	},
	costDetails: {
		...getCurrencySettings(),
		value: 0,
	},
	fees: {
		availableFees: [],
		automaticFees: [],
		selectedFees: [],
	},
};

const updteTitle = _x( 'Update Ticket', 'Update ticket modal header title', 'event-tickets' );
const createTitle = _x( 'New Ticket', 'Create ticket modal header title', 'event-tickets' );

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
			eventId: getCurrentPostId() as number,
		};
	}, [] );

	const [ currentValues, setCurrentValues ] = useState< TicketSettings >( {
		eventId: eventId,
		...defaultValues,
		...value,
	} );

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
	}, [ confirmEnabled, currentValues, onSave ] );

	const onDeleteClicked = useCallback( (): void => {
		const id = currentValues.id as number;
		TicketApi.deleteTicket( id )
			.then( () => {
				setSaveInProgress( false );
				setTicketUpsertError( null );
				onDelete( id );
			} )
			.catch( ( error: Error ) => {
				setSaveInProgress( false );
				setTicketUpsertError( error );
			} );
	}, [ currentValues, onDelete ] );

	return (
		<Root title={ isUpdate ? updteTitle : createTitle } headerIcon={ <IconNew /> } type="ticket">
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

			<ClassyModalSection includeSeparator={ true }>
				<TicketName
					value={ decodeEntities( currentValues.name ) }
					onChange={ ( value: string ) => {
						const newValue = value || '';
						setConfirmEnabled( newValue.trim() !== '' );
						return onValueChange( 'name', newValue );
					} }
				/>

				<TicketDescription
					value={ decodeEntities( currentValues.description as string ) }
					onChange={ ( value: string ) => onValueChange( 'description', value || '' ) }
				/>

				<CurrencyInput
					label={ _x( 'Ticket Price', 'Label for the ticket price field', 'event-tickets' ) }
					value={ currentValues.costDetails.value > 0 ? currentValues.costDetails.value.toString() : '' }
					onChange={ ( value: string ) => {
						const numericValue = parseFloat( value );
						const newValues = currentValues;

						if ( isNaN( numericValue ) ) {
							delete newValues.costDetails.value;
							return onValueChange( 'costDetails', newValues.costDetails );
						}

						return onValueChange( 'costDetails', {
							...currentValues.costDetails,
							value: numericValue,
						} );
					} }
				/>

				<SalePrice
					value={ currentValues.salePriceData as SalePriceDetails }
					onChange={ ( value: SalePriceDetails ) => onValueChange( 'salePriceData', value ) }
				/>
			</ClassyModalSection>

			<ClassyModalSection
				title={ _x( 'Capacity', 'Title for the capacity section in the Classy editor', 'event-tickets' ) }
				includeSeparator={ true }
			>
				<Capacity
					value={ currentValues.capacitySettings as CapacitySettings }
					onChange={ ( value: CapacitySettings ) =>
						onValueChange( 'capacitySettings', value as CapacitySettings )
					}
				/>
			</ClassyModalSection>

			<ClassyModalSection
				title={ _x(
					'Sale Duration',
					'Title for the sale duration section in the Classy editor',
					'event-tickets'
				) }
			>
				<SaleDuration
					saleStart={ currentValues.availableFrom as Date | '' }
					saleEnd={ currentValues.availableUntil as Date | '' }
				/>
			</ClassyModalSection>

			<Footer type="ticket">
				<Actions type="ticket">
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
				</Actions>
			</Footer>
		</Root>
	);
}
