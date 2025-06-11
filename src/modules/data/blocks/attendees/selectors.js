/**
 * External dependencies
 */
import { createSelector } from 'reselect';

export const getAttendeesBlock = ( state ) => state.tickets.blocks.attendees;

export const getTitle = createSelector( [ getAttendeesBlock ], ( attendees ) => attendees.title );

export const getDisplayTitle = createSelector( [ getAttendeesBlock ], ( attendees ) => attendees.displayTitle );

export const getDisplaySubtitle = createSelector( [ getAttendeesBlock ], ( attendees ) => attendees.displaySubtitle );
