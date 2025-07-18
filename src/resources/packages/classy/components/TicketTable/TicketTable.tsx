import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { Ticket } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { TicketRow } from '../TicketRow';

type TicketTableProps = {
	tickets: Ticket[];
	onEditTicket: ( ticket: Ticket ) => void;
} & Omit<TicketComponentProps, 'value'>;

const sortTickets = ( tickets: Ticket[] ): Ticket[] => {
	return tickets.toSorted( ( a: Ticket, b: Ticket ) => {
		if ( a.menuOrder < b.menuOrder ) {
			return -1;
		} else if ( a.menuOrder > b.menuOrder ) {
			return 1;
		} else {
			return 0;
		}
	} );
};

/**
 * TicketTable component for displaying a list of tickets in a table format.
 *
 * @since TBD
 *
 * @param {TicketTableProps} props
 */
export default function TicketTable( props: TicketTableProps ): JSX.Element {
	const {
		tickets,
		onEditTicket,
	} = props;

	const [ orderedTickets, setOrderedTickets ] = React.useState<Ticket[]>( sortTickets( tickets ) );


	if ( ! tickets || tickets.length === 0 ) {
		return <p>{ _x( 'No tickets available.', 'Message when no tickets are present', 'event-tickets' ) }</p>;
	}

	return (
		<table className="classy-field classy-field__ticket-table">
			<tbody>
				{ orderedTickets.map( ( ticket: Ticket, index: number ) => (
					<TicketRow
						key={ ticket.id }
						value={ ticket }
						onEdit={ onEditTicket }
						showMovers={ tickets.length > 1 }
						tabIndex={ index + 1 }
					/>
				) ) }
			</tbody>
		</table>
	);
}
