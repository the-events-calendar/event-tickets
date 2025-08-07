import * as React from 'react';
import { useCallback } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { TicketSettings } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { TicketRow } from '../TicketRow';
import { STORE_NAME } from '../../constants';
import { StoreDispatch, StoreSelect, CoreEditorSelect } from "../../types/Store";

type TicketTableProps = {
	onEditTicket: ( ticket: TicketSettings ) => void;
} & Omit<TicketComponentProps, 'value'>;

type MoveDirection = 'up' | 'down';

const moveTicket = ( tickets: TicketSettings[], direction: MoveDirection, index: number ): TicketSettings[] => {
	const newTickets = [ ...tickets ];
	const ticketToMove = newTickets[ index ];

	if ( direction === 'up' && index > 0 ) {
		newTickets.splice( index, 1 );
		newTickets.splice( index - 1, 0, ticketToMove );
	} else if ( direction === 'down' && index < newTickets.length - 1 ) {
		newTickets.splice( index, 1 );
		newTickets.splice( index + 1, 0, ticketToMove );
	}

	return newTickets;
}

/**
 * TicketTable component for displaying a list of tickets in a table format.
 *
 * @since TBD
 *
 * @param {TicketTableProps} props
 */
export default function TicketTable( props: TicketTableProps ): JSX.Element {
	const {
		onEditTicket,
	} = props;

	const { tickets } = useSelect( ( select ) => {
		const { getTickets }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() ),
		};
	}, [] );

	const { setTickets }: StoreDispatch = useDispatch( STORE_NAME );

	const showMovers = tickets.length > 1;
	const ticketLength = tickets.length;

	// todo: update the menu order of the tickets when moving them.

	const handleMoveTicket = useCallback( ( direction: MoveDirection, index: number ) => {
		const updatedTickets = moveTicket( tickets, direction, index );
		setTickets( updatedTickets );
	}, [ tickets, setTickets ] );

	return (
		<table className="classy-field classy-field__ticket-table">
			<tbody>
				{ tickets.map( ( ticket: TicketSettings, index: number ) => (
					<TicketRow
						key={ ticket.id }
						value={ ticket }
						onEdit={ onEditTicket }
						onMoveDown={ () => handleMoveTicket( 'down', index ) }
						onMoveUp={ () => handleMoveTicket( 'up', index ) }
						showMovers={ showMovers }
						canMoveUp={ index > 0 }
						canMoveDown={ index < ticketLength - 1 }
						tabIndex={ index + 1 }
						ticketPosition={ index }
					/>
				) ) }
			</tbody>
		</table>
	);
}
