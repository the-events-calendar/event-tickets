import { CenteredSpinner, ErrorBoundary, IconTicket } from '@tec/common/classy/components';
import { Button, Fill } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { useCallback, useEffect, useState } from 'react';
import { AddTicket, TicketTable, TicketUpsertModal } from '../../components';
import { STORE_NAME } from '../../constants';
import { CoreEditorSelect, StoreDispatch, StoreSelect } from '../../types/Store';
import { TicketId, TicketSettings } from '../../types/Ticket';
import { STORE_NAME as TEC_STORE_NAME } from '@tec/events/classy/constants';
import { StoreDispatch as TECStoreDispatch } from '@tec/events/classy/types/Store';

const defaultTicket: TicketSettings = {
	name: '',
	description: '',
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
};

/**
 * Tickets component to display and manage tickets for an event.
 *
 * @since TBD
 *
 * @return {JSX.Element} The rendered component.
 */
export default function Tickets(): JSX.Element {
	const { tickets, isLoading } = useSelect( ( select: SelectFunction ) => {
		const { getTickets, isLoading }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() ),
			isLoading: isLoading(),
		};
	}, [] );

	const { addTicket, deleteTicket, updateTicket }: StoreDispatch = useDispatch( STORE_NAME );

	const [ isUpserting, setIsUpserting ] = useState( false );
	const [ isNewTicket, setIsNewTicket ] = useState( false );
	const [ ticketToEdit, setTicketToEdit ] = useState< TicketSettings >( defaultTicket );

	const onTicketAddedClicked = useCallback( () => {
		setIsUpserting( true );
		setIsNewTicket( true );
	}, [] );

	const onTicketUpsertSaved = useCallback(
		( ticket: TicketSettings ) => {
			if ( isNewTicket ) {
				addTicket( ticket );
			} else {
				updateTicket( ticket.id, ticket );
			}

			setIsUpserting( false );
			setTicketToEdit( defaultTicket );
		},
		[ isNewTicket, defaultTicket ]
	);

	const onEditTicket = useCallback( ( ticket: TicketSettings ) => {
		setTicketToEdit( ticket );
		setIsUpserting( true );
		setIsNewTicket( false );
	}, [] );

	const onTicketEditCancelled = useCallback( () => {
		setIsUpserting( false );
		setTicketToEdit( defaultTicket );
	}, [ defaultTicket ] );

	const onTicketDeleted = useCallback( ( ticketId: TicketId ) => {
		deleteTicket( ticketId );
		setIsUpserting( false );
		setTicketToEdit( defaultTicket );
	}, [] );

	const { setIsUsingTickets }: TECStoreDispatch = useDispatch( TEC_STORE_NAME );
	useEffect( () => {
		if ( isLoading ) {
			return;
		}

		setIsUsingTickets( tickets.length > 0 );
	}, [ tickets, isLoading, setIsUsingTickets ] );

	// If the tickets are not yet loaded, show a spinner.
	if ( isLoading ) {
		return <CenteredSpinner />;
	}

	const addTicketText =
		tickets.length > 0
			? _x( 'Add Ticket', 'Button text to add a new ticket when tickets already exist', 'event-tickets' )
			: _x( 'Add Tickets', 'Button text to add a new ticket when no tickets exist', 'event-tickets' );

	return (
		<ErrorBoundary
			errorMessage={ _x(
				'There was an error in the tickets component:',
				'Error message for loading tickets',
				'event-tickets'
			) }
		>
			{ /* Portal-render the Sell Tickets button */ }
			<Fill name="tec.classy.fields.event-admission.buttons">
				<Button className="classy-button" __next40pxDefaultSize variant="primary" onClick={ (): void => {} }>
					<IconTicket className="classy-icon--prefix" />
					{ _x( 'Sell Tickets', 'Event admission button label', 'event-tickets' ) }
				</Button>
			</Fill>

			<div className="classy-field classy-field--tickets">
				<div className="classy-field__input-title">
					<h3>{ _x( 'Tickets', 'Title for Tickets section', 'event-tickets' ) }</h3>
				</div>

				{ isUpserting && (
					<TicketUpsertModal
						isUpdate={ ! isNewTicket }
						onCancel={ onTicketEditCancelled }
						onClose={ onTicketEditCancelled }
						onDelete={ onTicketDeleted }
						onSave={ onTicketUpsertSaved }
						value={ ticketToEdit }
					/>
				) }

				<TicketTable onEditTicket={ onEditTicket } />

				<AddTicket buttonText={ addTicketText } onClick={ onTicketAddedClicked } />
			</div>
		</ErrorBoundary>
	);
}
