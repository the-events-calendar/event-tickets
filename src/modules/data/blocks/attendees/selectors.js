/**
 * External dependencies
 */
import { createSelector } from 'reselect';

export const getAttendeesBlock = ( state ) => state.tickets.blocks.attendees;

export const getTitle = createSelector(
	[ getAttendeesBlock ],
	( attendees ) => attendees.title,
);
