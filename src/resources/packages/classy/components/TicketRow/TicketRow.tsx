import * as React from 'react';
import { Slot } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { _x } from '@wordpress/i18n';
import { formatCurrency } from '@tec/common/classy/functions';
import { CapacitySettings, TicketSettings } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { ClockIcon, TimerIcon } from '../Icons';
import { TicketRowMover } from '../TicketRowMover';

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
	const { enteredCapacity, isShared, sharedCapacity = 0 } = settings;
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
};

/**
 * Generates ticket icons based on the ticket's availability dates.
 *
 * This function checks the ticket's `availableFrom` and `availableUntil` dates
 * to determine if the ticket sales start in the future or have ended in the past.
 * It then returns a JSX element containing the appropriate icons.
 *
 * @since TBD
 *
 * @param {TicketSettings} ticket The ticket settings object to evaluate.
 * @return {React.JSX.Element} A JSX element containing the relevant ticket icons.
 */
const getTicketIcons = ( ticket: TicketSettings ): React.JSX.Element => {
	const now = new Date();
	let salesStartInFuture = false;
	let salesEndedInPast = false;

	// Determine if the ticket sales start in the future.
	if ( ticket.availableFrom ) {
		const availableFromDate = new Date( ticket.availableFrom );
		if ( availableFromDate > now ) {
			salesStartInFuture = true;
		}
	}

	// Determine if the ticket sales have ended in the past.
	if ( ticket.availableUntil ) {
		const availableUntilDate = new Date( ticket.availableUntil );
		if ( availableUntilDate < now ) {
			salesEndedInPast = true;
		}
	}

	return (
		<span className="classy-field__ticket-row__icons">
			{ salesStartInFuture && <ClockIcon /> }
			{ salesEndedInPast && <TimerIcon /> }
			{
				/**
				 * Renders in the Ticket Row Icons slot, after the default icons.
				 *
				 * This slot allows for additional icons to be added to the ticket row. While
				 * adding other elements is possible, only icons are recommended to maintain
				 * visual consistency.
				 *
				 * To add custom icons to this slot, use the `tec.classy.render` filter to render
				 * a `Fill` component targeting the `tec.tickets.classy.ticketRow.icons` slot. As
				 * a child of the `Fill`, use a function that accepts the `{ ticket }` prop and returns
				 * the desired icon(s) to be rendered.
				 *
				 * @since TBD
				 *
				 * Example:
				 * ```tsx
				 * addFilter(
				 *	'tec.classy.render',
				 *	'tec.classy.my-plugin',
				 *	(fields: React.ReactNode | null) => (
				 *		<Fragment>
				 *			{fields}
				 *			<Fill name='tec.tickets.classy.ticketRow.icons'>
				 *				{ ( { ticket }: { ticket: TicketSettings; } ) => (
				 *					if ( someConditionBasedOnTicket( ticket ) ) {
				 *						return <MyCustomIcon />;
				 *					}
				 *					return null;
				 *				) }
				 *			</Fill>
				 *		</Fragment>
				 *	)
				 * );
				 * ```
				 *
				 * @param {Object} props The properties passed to the slot.
				 * @param {TicketSettings} props.ticket The ticket settings object.
				 */
				<Slot name="tec.tickets.classy.ticketRow.icons" fillProps={ { ticket } } />
			}
		</span>
	);
};

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
		value: ticket,
	} = props;

	const icons = useMemo( () => getTicketIcons( ticket ), [ ticket ] );

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
					{ icons }
				</h4>
				{ ticket.description && (
					<span className="classy-field__ticket-row__description">
						{ decodeEntities( ticket.description ) }
					</span>
				) }
			</td>

			<td className="classy-field__ticket-row__price classy-field__ticket-row__section">
				{ ticket.cost
					? formatCurrency( { value: ticket.cost } )
					: _x( 'Free', 'Label for a free ticket', 'event-tickets' ) }
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
