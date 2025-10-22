import * as React from 'react';
import { useCallback } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { TicketSettings } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { TicketRow } from '../TicketRow';
import { STORE_NAME } from '../../constants';
import { StoreDispatch, StoreSelect } from '../../types/Store';
import { CoreEditorSelect } from '@tec/common/classy/types/Store';

type TicketTableProps = {
	onEditTicket: ( ticket: TicketSettings ) => void;
} & Omit< TicketComponentProps, 'value' >;

type MoveDirection = 'up' | 'down';

/**
 * Move a ticket up or down in the list.
 *
 * @since TBD
 *
 * @param {TicketSettings[]} tickets The array of tickets.
 * @param {MoveDirection} direction The direction to move the ticket ('up' or 'down').
 * @param {number} index The index of the ticket to move.
 * @return {TicketSettings[]} The updated array of tickets.
 */
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
};

/**
 * TicketTable component for displaying a list of tickets in a table format.
 *
 * @since TBD
 *
 * @param {TicketTableProps} props The properties for the TicketTable component.
 * @return {React.JSX.Element | null} The rendered TicketTable component or null if there are no tickets.
 */
export default function TicketTable( props: TicketTableProps ): React.JSX.Element | null {
	const { onEditTicket } = props;

	const { tickets } = useSelect( ( select ) => {
		const { getTickets }: StoreSelect = select( STORE_NAME );
		const { getCurrentPostId }: CoreEditorSelect = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() as number ),
		};
	}, [] );

	const { setTickets }: StoreDispatch = useDispatch( STORE_NAME );

	const showMovers = tickets.length > 1;

	// todo: update the menu order of the tickets when moving them.

	const handleMoveTicket = useCallback(
		( direction: MoveDirection, index: number ) => {
			const updatedTickets = moveTicket( tickets, direction, index );
			setTickets( updatedTickets );
		},
		[ tickets ]
	);

	// If there are no tickets, return null to avoid rendering an empty table.
	if ( tickets.length === 0 ) {
		return null;
	}

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
						canMoveDown={ index < tickets.length - 1 }
						tabIndex={ index + 1 }
						ticketPosition={ index }
					/>
				) ) }
			</tbody>
		</table>
	);
}
