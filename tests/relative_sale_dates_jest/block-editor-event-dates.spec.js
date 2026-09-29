import { readEventDates } from '@tec/tickets/relative-sale-dates/block-editor/event-dates';
import { DEFAULT_EVENT, clearBlockEditorGlobals, setBlockEditorData, setEventState } from './block-editor-event-state';

describe( 'readEventDates', () => {
	beforeEach( () => {
		setBlockEditorData();
	} );

	afterEach( () => {
		clearBlockEditorGlobals();
	} );

	it( 'should read the start, end and timezone of a timed event', () => {
		setEventState( DEFAULT_EVENT );

		expect( readEventDates() ).toStrictEqual( {
			start: DEFAULT_EVENT.start,
			end: DEFAULT_EVENT.end,
			timezone: DEFAULT_EVENT.timeZone,
		} );
	} );

	it( 'should read a manual UTC offset as the zone the server resolves it to', () => {
		setBlockEditorData( { timezones: { 'UTC+3': 'Europe/Istanbul' } } );
		setEventState( { ...DEFAULT_EVENT, timeZone: 'UTC+3' } );

		expect( readEventDates().timezone ).toBe( 'Europe/Istanbul' );
	} );

	it( 'should read an all-day event with the times the server saves for one', () => {
		setBlockEditorData( { allDay: { start: '06:00:00', end: '05:59:59', endDays: 1 } } );
		setEventState( {
			start: '2040-10-20 00:00:00',
			end: '2040-10-21 23:59:59',
			allDay: true,
			timeZone: 'America/Sao_Paulo',
		} );

		expect( readEventDates() ).toStrictEqual( {
			start: '2040-10-20 06:00:00',
			end: '2040-10-22 05:59:59',
			timezone: 'America/Sao_Paulo',
		} );
	} );

	it( 'should read nothing when The Events Calendar has not loaded its datetime store', () => {
		setEventState( { ...DEFAULT_EVENT, timeZone: 'UTC' } );
		delete window.tec.events;

		expect( readEventDates() ).toBeNull();
	} );

	it( 'should read nothing while the event has no timezone', () => {
		setEventState( { ...DEFAULT_EVENT, timeZone: '' } );

		expect( readEventDates() ).toBeNull();
	} );

	it( 'should read nothing while the event has no start', () => {
		setEventState( { ...DEFAULT_EVENT, start: '' } );

		expect( readEventDates() ).toBeNull();
	} );
} );
