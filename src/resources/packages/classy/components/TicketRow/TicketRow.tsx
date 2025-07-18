import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { PartialTicket } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { ClipboardIcon, ClockIcon } from '../Icons';
import { TicketRowMover } from "../TicketRowMover";

type TicketRowProps = {
	canMoveDown?: boolean;
	canMoveUp?: boolean;
	onEdit: ( ticket: PartialTicket ) => void;
	onMoveDown?: () => void;
	onMoveUp?: () => void;
	showMovers?: boolean;
	tabIndex?: number;
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
		canMoveDown = false,
		canMoveUp = false,
		onEdit,
		onMoveDown = () => {},
		onMoveUp = () => {},
		showMovers = false,
		tabIndex,
		ticketPosition = 0,
		value: ticket
	} = props;

	// todo: This should be based on whether any icons should be shown.
	const [ hasIcons, setHasIcons ] = React.useState( true );

	// todo: Calculations based on different capacity types.
	const capacity = ticket.capacity || 0;
	const capacityNumber = -1 !== capacity ? capacity : _x( 'Unlimited', 'Label for unlimited capacity', 'event-tickets' );

	return (
		<tr
			aria-label={ ticket.title }
			className="classy-field classy-field__ticket-row"
			onClick={ () => onEdit( ticket ) }
			tabIndex={ tabIndex }
		>
			<td className="classy-field__ticket-row__label classy-field__ticket-row__section">
				<h4>
					{ ticket.title }
					{ hasIcons && (
						<span className="classy-field__ticket-row__icons">
							{ /* todo: fill in icons properly */ }
							<ClipboardIcon/>
							<ClockIcon/>
						</span>
					) }
				</h4>
				{ ticket.description && (
					<span className="classy-field__ticket-row__description">{ decodeEntities( ticket.description ) }</span>
				) }
			</td>

			<td className="classy-field__ticket-row__price classy-field__ticket-row__section">
				{ ticket.cost || _x( 'Free', 'Label for a free ticket', 'event-tickets' ) }
			</td>

			<td className="classy-field__ticket-row__capacity classy-field__ticket-row__section">
				{ capacityNumber }
				<span className="classy-field__ticket-row__capacity__label">
					{ _x( 'tickets', 'Label for the number of tickets available', 'event-tickets' ) }
				</span>
			</td>

			{ showMovers && (
				<td className="classy-field__ticket-row__movers classy-field__ticket-row__section">
					{ /* todo: implement component */}
					<TicketRowMover
						canMoveUp={ canMoveUp }
						canMoveDown={ canMoveDown }
						onMoveUp={ onMoveUp }
						onMoveDown={ onMoveDown }
						rowLabel={ ticket.title }
						ticketPosition={ ticketPosition }
					/>
				</td>
			) }
		</tr>
	);
}
