import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { CapacitySettings, TicketSettings } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { ClipboardIcon, ClockIcon } from '../Icons';
import { TicketRowMover } from "../TicketRowMover";

type TicketRowProps = {
	canMoveDown?: boolean;
	canMoveUp?: boolean;
	onEdit: ( ticket: TicketSettings ) => void;
	onMoveDown?: () => void;
	onMoveUp?: () => void;
	showMovers?: boolean;
	tabIndex?: number;
	ticketPosition?: number;
	value: TicketSettings;
} & TicketComponentProps;

const unlimitedLowercase = _x( 'unlimited', 'Label for unlimited capacity', 'event-tickets' );

/**
 * Calculates and returns the appropriate capacity number based on the settings provided.
 *
 * The function evaluates the entered capacity, whether it is marked as unlimited
 * or shared, and compares it with a shared capacity if applicable. The final
 * capacity value is determined based on the logic described below:
 *
 * - If the entered capacity is unlimited (`''`), the result is `unlimitedLowercase`.
 * - If the capacity is not shared, the entered capacity is returned as-is.
 * - If the capacity is shared but a shared capacity is not provided, the entered capacity is returned.
 * - If the entered capacity is less than or equal to the shared capacity, the entered capacity is returned.
 * - If the entered capacity exceeds the shared capacity, the shared capacity is returned.
 *
 * @since TBD
 *
 * @param {CapacitySettings} settings - The settings object containing capacity details.
 * @returns {string|number} The calculated capacity based on the input settings. Returns `unlimitedLowercase` for unlimited capacity.
 */
const getCapacityNumber = ( settings: CapacitySettings ): string | number => {
	const {
		enteredCapacity,
		isShared,
		sharedCapacity = 0
	} = settings;
	const enteredIsUnlimited = '' === enteredCapacity || -1 === enteredCapacity;
	const enteredAsNumber = enteredIsUnlimited ? -1 : Number( enteredCapacity );

	// If it's not shared, just return the entered capacity.
	if ( ! isShared ) {
		return enteredIsUnlimited ? unlimitedLowercase : enteredCapacity;
	}

	// If it is shared, but we don't have a shared capacity, return the entered capacity.
	if ( ! sharedCapacity ) {
		return enteredIsUnlimited ? unlimitedLowercase : enteredCapacity;
	}

	// If the entered capacity is less than or equal to the shared capacity, return the entered capacity.
	// Otherwise, return the shared capacity.
	if ( enteredAsNumber <= sharedCapacity ) {
		return enteredIsUnlimited ? unlimitedLowercase : enteredCapacity;
	} else {
		return sharedCapacity;
	}
}

const noop = () => {};

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
		onMoveDown = noop,
		onMoveUp = noop,
		showMovers = false,
		tabIndex,
		ticketPosition = 0,
		value: ticket
	} = props;

	// todo: This should be based on whether any icons should be shown.
	const [ hasIcons, setHasIcons ] = React.useState( true );

	return (
		<tr
			aria-label={ ticket.name }
			className="classy-field classy-field__ticket-row"
			onClick={ () => onEdit( ticket ) }
			tabIndex={ tabIndex }
		>
			<td className="classy-field__ticket-row__label classy-field__ticket-row__section">
				<h4>
					{ ticket.name }
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
				<span className="classy-field__ticket-row__capacity__label">
					{ getCapacityNumber( ticket.capacitySettings ) }
					&nbsp;
					{ _x( 'tickets', 'Label for the number of tickets available', 'event-tickets' ) }
				</span>
			</td>

			{ showMovers && (
				<td className="classy-field__ticket-row__movers classy-field__ticket-row__section">
					<TicketRowMover
						canMoveUp={ canMoveUp }
						canMoveDown={ canMoveDown }
						onMoveUp={ onMoveUp }
						onMoveDown={ onMoveDown }
						rowLabel={ ticket.name }
						ticketPosition={ ticketPosition }
					/>
				</td>
			) }
		</tr>
	);
}
