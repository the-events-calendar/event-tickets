/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import rsvp from './rsvp';
import ticket from './ticket';
import attendees from './attendees';

export default combineReducers( {
	rsvp,
	ticket,
	attendees,
} );
