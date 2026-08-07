/**
 * Internal dependencies
 */
import * as actions from '../actions';
import reducer, { DEFAULT_STATE } from '../reducer';

describe( 'rsvp-shared reducer', () => {
	it( 'defaults iac to none', () => {
		expect( DEFAULT_STATE.iac ).toBe( 'none' );
	} );

	it( 'sets the IAC value to allowed', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPIAC( 'allowed' ) ).iac ).toBe( 'allowed' );
	} );

	it( 'sets the IAC value to required', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPIAC( 'required' ) ).iac ).toBe( 'required' );
	} );

	it( 'resets the IAC value to none', () => {
		const modifiedState = { ...DEFAULT_STATE, iac: 'allowed' };
		expect( reducer( modifiedState, actions.setRSVPIAC( 'none' ) ).iac ).toBe( 'none' );
	} );
} );
