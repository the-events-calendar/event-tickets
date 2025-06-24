import * as React from 'react';
import { Fragment, useEffect, useState } from 'react';
import { _x } from '@wordpress/i18n';
import {
	AddTicket,
	TicketUpsertModal,
} from '../../components';
import { Ticket as TicketData } from '../../types/Ticket';

type TicketsProps = {
	tickets: []
};

const defaultTicket: TicketData = {
	name: '',
	description: '',
	price: '',
	hasSalePrice: false,
	salePrice: '',
	capacityType: 'general-admission',
	selectedFees: [],
	displayedFees: [],
	capacity: '',
	capacityShared: false
};

export default function Tickets( props: TicketsProps ): JSX.Element {

	const { tickets } = props;

	const [ hasTickets, setHasTickets ] = useState( false );

	const [ isUpserting, setIsUpserting ] = useState( false );

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
