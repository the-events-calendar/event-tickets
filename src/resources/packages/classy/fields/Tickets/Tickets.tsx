import * as React from 'react';
import { Fragment, useCallback, useState } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { CenteredSpinner } from '@tec/common/classy/components';
import { _x } from '@wordpress/i18n';
import {
	AddTicket,
	TicketUpsertModal,
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
	const { tickets, isLoading } = useSelect( ( select: SelectFunction ) => {
		const { getTickets, isLoading }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() ) || null,
			isLoading: isLoading(),
		};
	}, [] );

	const {
		addTicket,
		deleteTicket,
		updateTicket,
	}: StoreDispatch = useDispatch( STORE_NAME );

	const [ hasTickets, setHasTickets ] = useState( tickets.length > 0 );
	const [ isUpserting, setIsUpserting ] = useState( false );
	const [ isNewTicket, setIsNewTicket ] = useState( false );

	const onTicketAddedClicked = useCallback( () => {
		setIsUpserting( true );
		setIsNewTicket( true );
	}, [] );

	const onTicketUpsertSaved = useCallback( ( ticket: TicketData ) => {
		if ( isNewTicket ) {
			TicketApi.createTicket( ticket )
				.then( () => {
					addTicket( ticket );
				} )
				.catch( ( error: Error ) => {
					console.error( 'Error creating ticket:', error );
				} );
		} else {
			TicketApi.updateTicket( ticket.id, ticket )
				.then( () => {
					updateTicket( ticket.id, ticket );
				} )
				.catch( ( error: Error ) => {
					console.error( 'Error updating ticket:', error );
				} );
		}

		setIsUpserting( false );
		setIsNewTicket( false );
	}, [ isNewTicket ] );

	// If the tickets are not yet loaded, show a spinner.
	if ( isLoading ) {
		return <CenteredSpinner />;
	}

	return (
		<div className="classy-field classy-field--tickets">
			{ isUpserting && (
				<TicketUpsertModal
					isUpdate={ ! isNewTicket }
					onCancel={ () => setIsUpserting( false ) }
					onClose={ () => setIsUpserting( false ) }
					onSave={ onTicketUpsertSaved }
					values={ defaultTicket }
				/>
			) }

			{ tickets.map( ( ticket: TicketData ) => (
				<div key={ ticket.id }>
					<pre><code style={ { display: "block" } }>{ JSON.stringify( ticket, null, "\t" ) }</code></pre>
				</div>
			) ) }

			<AddTicket
				buttonText={ _x( 'Add Tickets', 'Button text to add a new ticket', 'event-tickets' ) }
				onClick={ onTicketAddedClicked }
			/>
		</div>
	);
}
