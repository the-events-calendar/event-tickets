/**
 *  External Dependencies
 */
import ui, { DEFAULT_STATE } from '../index';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';
import tmp from '../tmp';

jest.mock( '../tmp', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

describe( 'Settings reducer', () => {
	test( 'default state', () => {
		expect( ui( undefined, {} ) ).toBe( DEFAULT_STATE );
	} );

	test( 'Shared capacity', () => {
		expect( ui( DEFAULT_STATE, actions.setTotalSharedCapacity( 50 ) ) ).toMatchSnapshot();
	} );

	test( 'Tmp actions are passed down to the reducer', () => {
		ui( DEFAULT_STATE, actions.setTempSharedCapacity( 100 ) );
		expect( tmp ).toHaveBeenCalledTimes( 1 );
		expect( tmp ).toHaveBeenCalledWith( DEFAULT_STATE.tmp, actions.setTempSharedCapacity( 100 ) );
	} );
} );
