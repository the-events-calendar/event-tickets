import {
	createTypingTracker,
	getShownError,
	TYPING_SETTLE_DELAY,
} from '@tec/tickets/relative-sale-dates/live-error';

describe( 'getShownError', () => {
	it( 'should hold a new error back while the admin types', () => {
		expect( getShownError( null, 'error', true ) ).toBeNull();
		expect( getShownError( '', 'error', true ) ).toBeNull();
	} );

	it( 'should show a new error once the admin is not typing', () => {
		expect( getShownError( null, 'error', false ) ).toBe( 'error' );
	} );

	it( 'should follow an error already shown while the admin types', () => {
		expect( getShownError( 'error', 'other', true ) ).toBe( 'other' );
		expect( getShownError( 'error', null, true ) ).toBeNull();
	} );
} );

describe( 'createTypingTracker', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'should count the admin as typing until the settle delay passes without a keystroke', () => {
		const onSettle = jest.fn();
		const tracker = createTypingTracker( onSettle );

		tracker.type();
		jest.advanceTimersByTime( TYPING_SETTLE_DELAY - 1 );
		tracker.type();
		jest.advanceTimersByTime( TYPING_SETTLE_DELAY - 1 );

		expect( tracker.isTyping() ).toBe( true );
		expect( onSettle ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( 1 );

		expect( tracker.isTyping() ).toBe( false );
		expect( onSettle ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should stop counting the admin as typing once the edit is done, without waiting for the delay', () => {
		const onSettle = jest.fn();
		const tracker = createTypingTracker( onSettle );

		tracker.type();
		tracker.settle();
		jest.advanceTimersByTime( TYPING_SETTLE_DELAY );

		expect( tracker.isTyping() ).toBe( false );
		expect( onSettle ).not.toHaveBeenCalled();
	} );
} );
