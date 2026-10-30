/**
 * Tickets by Date in the classic editor: the section under a recurring event's tickets.
 *
 * The tickets panel prints the section's root with the event's data as of its last save. The panel is re-rendered
 * after a ticket is saved, so a new root is drawn when it shows up. Saving the event reloads the page with fresh data.
 *
 * @since TBD
 */
import React from 'react';
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import TicketsByDate from './section';

const rootSelector = '.tec-tickets-by-date-root';
const datesChangeSelector =
	'[name^="recurrence["], #EventStartDate, #EventEndDate, #EventStartTime, #EventEndTime, #allDayCheckbox';

/**
 * The sections drawn, by their root element.
 *
 * @since TBD
 *
 * @type {Map<Element, {root: Object, data: Object}>}
 */
const sections = new Map();

/**
 * Whether tickets or dates changed since the event was last saved.
 *
 * @since TBD
 *
 * @type {boolean}
 */
let unsaved = false;

/**
 * Draws a section.
 *
 * @since TBD
 *
 * @param {{root: Object, data: Object}} section The section.
 *
 * @return {void}
 */
const draw = ( section ) => section.root.render( <TicketsByDate data={ section.data } unsaved={ unsaved } /> );

/**
 * Draws the section in every root not drawn yet, and forgets the roots gone with a re-rendered panel.
 *
 * @since TBD
 *
 * @return {void}
 */
const drawAll = () => {
	sections.forEach( ( section, element ) => {
		if ( ! element.isConnected ) {
			section.root.unmount();
			sections.delete( element );
		}
	} );

	document.querySelectorAll( rootSelector ).forEach( ( element ) => {
		if ( sections.has( element ) ) {
			return;
		}

		let data;
		try {
			data = JSON.parse( element.dataset.ticketsByDate );
		} catch ( error ) {
			return;
		}

		const section = { root: createRoot( element ), data };
		sections.set( element, section );
		draw( section );
	} );
};

/**
 * Says, once, that tickets or dates have changes not saved yet.
 *
 * @since TBD
 *
 * @return {void}
 */
const markUnsaved = () => {
	if ( unsaved ) {
		return;
	}

	unsaved = true;
	sections.forEach( draw );
};

domReady( () => {
	drawAll();

	const tickets = document.getElementById( 'tribetickets' );
	if ( tickets ) {
		new window.MutationObserver( drawAll ).observe( tickets, { childList: true, subtree: true } );
	}

	// The tickets panel script announces a ticket saved or deleted with jQuery events.
	window.jQuery?.( document ).on( 'pre-save-ticket.tribe delete-ticket.tribe', markUnsaved );

	document.addEventListener( 'change', ( event ) => {
		if ( event.target instanceof Element && event.target.matches( datesChangeSelector ) ) {
			markUnsaved();
		}
	} );
} );
