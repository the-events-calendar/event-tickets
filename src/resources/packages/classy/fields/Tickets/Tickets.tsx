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
import { PartialTicket, Ticket as TicketData } from '../../types/Ticket';
import { STORE_NAME } from '../../constants';
import { StoreSelect, StoreDispatch, CoreEditorSelect } from '../../types/Store';

const defaultTicket: PartialTicket = {
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
	const { tickets, isLoading } = useSelect( ( select: SelectFunction ) => {
		const { getTickets, isLoading }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() ),
			isLoading: isLoading(),
		};
	}, [] );

	const {
		addTicket,
		deleteTicket,
		updateTicket,
	}: StoreDispatch = useDispatch( STORE_NAME );

	const [ isUpserting, setIsUpserting ] = useState( false );
	const [ isNewTicket, setIsNewTicket ] = useState( false );
	const [ ticketToEdit, setTicketToEdit ] = useState<PartialTicket>( defaultTicket );

	const hasTickets = tickets && tickets.length > 0;

	const onTicketAddedClicked = useCallback( () => {
		setIsUpserting( true );
		setIsNewTicket( true );
	}, [] );

	const onTicketUpsertSaved = useCallback( ( ticket: TicketData ) => {
		if ( isNewTicket ) {
			addTicket( ticket );
		} else {
			updateTicket( ticket.id, ticket );
		}
	}, [ isNewTicket ] );

	const onEditTicket = useCallback( ( ticket: PartialTicket ) => {
		setTicketToEdit( ticket );
		setIsUpserting( true );
		setIsNewTicket( false );
	}, [] );

	const onTicketEditCancelled = useCallback( () => {
		setIsUpserting( false );
		setTicketToEdit( defaultTicket );
	}, [ defaultTicket ] );

	// If the tickets are not yet loaded, show a spinner.
	if ( isLoading ) {
		return <CenteredSpinner/>;
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
					onCancel={ onTicketEditCancelled }
					onClose={ onTicketEditCancelled }
					onSave={ onTicketUpsertSaved }
					value={ ticketToEdit }
				/>
			) }

			<TicketTable
				tickets={ tickets }
				onEditTicket={ onEditTicket }
			/>

			<AddTicket
				buttonText={ addTicketText }
				onClick={ onTicketAddedClicked }
			/>
		</div>
	);
}
