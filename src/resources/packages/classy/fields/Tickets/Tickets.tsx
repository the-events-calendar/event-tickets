import * as React from 'react';
import { Fragment, useEffect, useState } from 'react';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { _x } from '@wordpress/i18n';
import {
	AddTicket,
	TicketUpsertModal,
} from '../../components';
import { Ticket as TicketData } from '../../types/Ticket';
import { STORE_NAME } from '../../constants';

type TicketsProps = {
	eventId: number
};

const defaultTicket: Partial<TicketData> = {
	title: '',
	description: '',
};

export default function Tickets( props: TicketsProps ): JSX.Element {

	const { eventId } = props;

	const { tickets } = useSelect( ( select: SelectFunction ) => {
		const {
			getTicketsByEventId,
		}: {
			getTicketsByEventId: ( eventId: number ) => TicketData[];
		} = select( STORE_NAME );
	}, [] )

	const [ hasTickets, setHasTickets ] = useState( false );

	// todo: default state is false.
	const [ isUpserting, setIsUpserting ] = useState( true );

	const onTicketAddedClicked = () => {
		console.log( 'Ticket added clicked' );
		setIsUpserting( true );
	}

	return (
		<div className="classy-field classy-field--tickets">
			{ ! hasTickets && (
				<AddTicket
					buttonText={ _x( 'Add Tickets', 'Button text to add a new ticket', 'event-tickets' ) }
					onClick={ onTicketAddedClicked }
				/>
			) }

			{ isUpserting && (
				<TicketUpsertModal
					isUpdate={ ! hasTickets }
					onCancel={ () => setIsUpserting( false ) }
					onClose={ () => setIsUpserting( false ) }
					onSave={ () => {} }
					values={ defaultTicket }
				/>
			) }
		</div>
	);
}
