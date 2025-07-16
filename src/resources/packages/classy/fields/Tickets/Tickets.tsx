import * as React from 'react';
import { Fragment, useCallback, useState } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { CenteredSpinner } from '@tec/common/classy/components';
import { _x } from '@wordpress/i18n';
import {
	AddTicket,
	TicketUpsertModal,
	TicketTable,
} from '../../components';
import { Ticket as TicketData } from '../../types/Ticket';
import { STORE_NAME } from '../../constants';
import { StoreSelect, StoreDispatch, CoreEditorSelect } from '../../types/Store';
import * as TicketApi from '../../api/tickets';

const defaultTicket: Partial<TicketData> = {
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
 * Tickets component to display and manage tickets for an event.
 *
 * @since TBD
 *
 * @return {JSX.Element} The rendered component.
 */
export default function Tickets(): JSX.Element {
	const { tickets, isLoading, eventId } = useSelect( ( select: SelectFunction ) => {
		const { getTickets, isLoading }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );
		const eventId = getCurrentPostId();

		return {
			tickets: getTickets( eventId ) || null,
			isLoading: isLoading(),
			eventId: eventId,
		};
	}, [] );

	const {
		addTicket,
		deleteTicket,
		updateTicket,
	}: StoreDispatch = useDispatch( STORE_NAME );

	const [ isUpserting, setIsUpserting ] = useState( false );
	const [ isNewTicket, setIsNewTicket ] = useState( false );

	const onTicketAddedClicked = useCallback( () => {
		setIsUpserting( true );
		setIsNewTicket( true );
	}, [] );

	const onTicketUpsertSaved = useCallback( ( ticket: TicketData ) => {
		// Ensure we have an eventId for the ticket.
		ticket.eventId = ticket.eventId || eventId;

		TicketApi.upsertTicket( ticket )
			.then( () => {
				if ( isNewTicket ) {
					addTicket( ticket );
				} else {
					updateTicket( ticket.id, ticket );
				}
			} )
			.catch( ( error: Error ) => {
				console.error( 'Error upserting ticket:', error );
			} );

		setIsUpserting( false );
		setIsNewTicket( false );
	}, [ isNewTicket ] );

	// If the tickets are not yet loaded, show a spinner.
	if ( isLoading ) {
		return <CenteredSpinner />;
	}

	const addTicketText = tickets.length > 0
		? _x( 'Add Ticket', 'Button text to add a new ticket when tickets already exist', 'event-tickets' )
		: _x( 'Add Tickets', 'Button text to add a new ticket when no tickets exist', 'event-tickets' );

	return (
		<div className="classy-field classy-field--tickets">
			<div className="classy-field__input-title">
				<h3>{ _x( 'Tickets', 'Title for Tickets section', 'event-tickets' ) }</h3>
			</div>

			{ isUpserting && (
				<TicketUpsertModal
					isUpdate={ ! isNewTicket }
					onCancel={ () => setIsUpserting( false ) }
					onClose={ () => setIsUpserting( false ) }
					onSave={ onTicketUpsertSaved }
					values={ defaultTicket }
				/>
			) }

			<TicketTable
				tickets={ tickets }
				onEditTicket={ () => {} }
			/>

			<AddTicket
				buttonText={ addTicketText }
				onClick={ onTicketAddedClicked }
			/>
		</div>
	);
}
