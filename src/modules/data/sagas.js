/**
 * Internal dependencies
 */
import { store } from '@moderntribe/common/store';
import { sagas as RSVPSagas } from '@moderntribe/tickets/data/blocks/rsvp';
import { sagas as TicketSagas } from '@moderntribe/tickets/data/blocks/ticket';
import { sagas as AttendeesSagas } from '@moderntribe/tickets/data/blocks/attendees';
import MoveSagas from '@moderntribe/tickets/data/shared/move/sagas';

export default () => {
	[
		RSVPSagas,
		TicketSagas,
		AttendeesSagas,
		MoveSagas,
	].forEach( sagas => store.run( sagas ) );
};
