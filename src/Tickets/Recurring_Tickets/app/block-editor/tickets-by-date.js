/**
 * Tickets by Date in the block editor: a button in the Tickets block's dashboard that opens the section in a modal.
 *
 * The data comes with the event the editor loads, and again with every save, in the `tec_tickets_by_date` field. The
 * list is as of that save: a ticket saved, added or removed since, or an edit of the dates, shows the unsaved notice.
 *
 * @since TBD
 */
import React, { useRef, useState, useSyncExternalStore } from 'react';
import { Button, Modal, Path, SVG } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import TicketsByDate from '../tickets-by-date/section';

/**
 * The calendar icon, as an SVG: the editor canvas is an iframe without the Dashicons font.
 *
 * @since TBD
 *
 * @type {Object}
 */
const calendarIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z" />
	</SVG>
);

/**
 * The meta of the event's dates and recurrence: an unsaved edit of any changes the dates the section lists.
 *
 * @since TBD
 *
 * @type {string[]}
 */
const DATES_META = [
	'_EventStartDate',
	'_EventEndDate',
	'_EventAllDay',
	'_EventTimezone',
	'_tribe_blocks_recurrence_rules',
	'_tribe_blocks_recurrence_exclusions',
];

/**
 * Whether the edited meta changes the event's dates or recurrence.
 *
 * Compares values: TEC's event blocks write every meta key to the edits when the editor loads, changed or not.
 *
 * @since TBD
 *
 * @param {Object} saved  The meta as saved.
 * @param {Object} edited The meta as edited.
 *
 * @return {boolean} Whether they differ.
 */
export const hasUnsavedDates = ( saved, edited ) =>
	DATES_META.some(
		( key ) => JSON.stringify( saved?.[ key ] ) !== JSON.stringify( edited?.[ key ] ?? saved?.[ key ] )
	);

/**
 * How many tickets the Tickets block has saved since the editor loaded.
 *
 * @since TBD
 *
 * @type {number}
 */
let ticketSaves = 0;

/**
 * What follows the saves.
 *
 * @since TBD
 *
 * @type {Set<Function>}
 */
const saveListeners = new Set();

/**
 * Counts a ticket the Tickets block is saving: the section lists tickets as of the event's last save.
 *
 * @since TBD
 *
 * @param {FormData} body The body of the request that saves the ticket.
 *
 * @return {FormData} The body.
 */
export const countTicketSave = ( body ) => {
	ticketSaves++;
	saveListeners.forEach( ( listener ) => listener() );

	return body;
};

/**
 * Follows the ticket saves.
 *
 * @since TBD
 *
 * @param {Function} listener Called on every save.
 *
 * @return {Function} Stops following them.
 */
const followTicketSaves = ( listener ) => {
	saveListeners.add( listener );

	return () => saveListeners.delete( listener );
};

/**
 * The "Tickets by Date" button and its modal, for an event with Tickets by Date.
 *
 * @since TBD
 *
 * @return {JSX.Element|null} The button, and the modal when open.
 */
export function TicketsByDateAction() {
	const [ open, setOpen ] = useState( false );
	const { data, datesEdited, ticketBlocks } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			data: editor.getCurrentPost()?.tec_tickets_by_date ?? null,
			datesEdited: hasUnsavedDates( editor.getCurrentPost()?.meta, editor.getEditedPostAttribute( 'meta' ) ),
			ticketBlocks: select( 'core/block-editor' ).getGlobalBlockCount( 'tribe/tickets-item' ),
		};
	}, [] );
	const saves = useSyncExternalStore( followTicketSaves, () => ticketSaves );

	// Every save of the event brings new data: the ticket saves before it are in it.
	const savesAtData = useRef( { data: null, saves: 0 } );
	if ( savesAtData.current.data !== data ) {
		savesAtData.current = { data, saves };
	}

	if ( ! data ) {
		return null;
	}

	return (
		<>
			<Button variant="secondary" icon={ calendarIcon } onClick={ () => setOpen( true ) }>
				{ __( 'Tickets by Date', 'event-tickets' ) }
			</Button>
			{ open && (
				<Modal
					title={ __( 'Tickets by Date', 'event-tickets' ) }
					size="large"
					onRequestClose={ () => setOpen( false ) }
				>
					<TicketsByDate
						data={ data }
						unsaved={
							datesEdited ||
							saves !== savesAtData.current.saves ||
							ticketBlocks !== ( data.dates[ 0 ]?.tickets.length ?? 0 )
						}
					/>
				</Modal>
			) }
		</>
	);
}

addFilter( 'tec.tickets.blocks.setBodyDetails', 'tec.tickets.recurringTickets.ticketsByDate', countTicketSave );
