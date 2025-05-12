/**
 * Internal dependencies
 */
import { store } from '@moderntribe/common/store';
import { sagas as RSVPSagas } from './blocks/rsvp';
import { sagas as TicketSagas } from './blocks/ticket';
import { sagas as AttendeesSagas } from './blocks/attendees';
import MoveSagas from './shared/move/sagas';

export default () => {
	[ RSVPSagas, TicketSagas, AttendeesSagas, MoveSagas ].forEach( ( sagas ) => store.run( sagas ) );
};
