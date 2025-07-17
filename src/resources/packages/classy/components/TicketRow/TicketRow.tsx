import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { PartialTicket } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { ClockIcon } from '../Icons';

type TicketRowProps = {
	onEdit: ( ticket: PartialTicket ) => void;
	showMovers?: boolean;
	value: PartialTicket;
} & TicketComponentProps;

/**
 * TicketRow component for rendering a single ticket row.
 *
 * @since TBD
 *
 * @param {TicketRowProps} props
 * @return {JSX.Element} The rendered ticket row component.
 */
export default function TicketRow( props: TicketRowProps ): JSX.Element {
	const {
		onEdit,
		showMovers = false,
		value: ticket
	} = props;

	// todo: This should be based on whether any icons should be shown.
	const [ hasIcons, setHasIcons ] = React.useState( true );

	// todo: Calculations based on different capacity types.
	const capacity = ticket.capacity || 0;
	const capacityNumber = -1 !== capacity ? capacity : _x( 'Unlimited', 'Label for unlimited capacity', 'event-tickets' );

	return (
		<div className="classy-field classy-field__ticket-row" onClick={ () => onEdit( ticket ) }>
			<div className="classy-field__ticket-row__label classy-field__ticket-row__section">
				<h4>
					{ ticket.title || _x( 'Untitled Ticket', 'Default title for a ticket', 'event-tickets' ) }
					{ hasIcons && (
						<span className="classy-field__ticket-row__icons">
							{ /* todo: fill in icons properly */ }
							<ClockIcon/>
						</span>
					) }
				</h4>
				{ ticket.description && (
					<span className="classy-field__ticket-row__description">{ decodeEntities( ticket.description ) }</span>
				) }
			</div>

			<div className="classy-field__ticket-row__price classy-field__ticket-row__section">
				{ ticket.cost || _x( 'Free', 'Label for a free ticket', 'event-tickets' ) }
			</div>

			<div className="classy-field__ticket-row__capacity classy-field__ticket-row__section">
				{ capacityNumber }
				<span className="classy-field__ticket-row__capacity__label">
					{ _x( 'tickets', 'Label for the number of tickets available', 'event-tickets' ) }
				</span>
			</div>
		</div>
	);
}
