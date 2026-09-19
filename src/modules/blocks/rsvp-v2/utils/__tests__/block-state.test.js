/**
 * Internal dependencies
 */
import { isCreating, isSavedSummary, showEditAffordances } from '../block-state';

describe( 'block-state', () => {
	it( 'isCreating when not created and add/edit open', () => {
		expect( isCreating( { created: false, isAddEditOpen: true } ) ).toBe( true );
		expect( isCreating( { created: false, isAddEditOpen: false } ) ).toBe( false );
		expect( isCreating( { created: true, isAddEditOpen: true } ) ).toBe( false );
	} );

	it( 'isSavedSummary when created and not in create form', () => {
		expect( isSavedSummary( { created: true, isAddEditOpen: false } ) ).toBe( true );
		expect( isSavedSummary( { created: true, isAddEditOpen: true } ) ).toBe( false );
		expect( isSavedSummary( { created: false, isAddEditOpen: false } ) ).toBe( false );
	} );

	it( 'showEditAffordances only when saved and selected', () => {
		expect( showEditAffordances( { created: true, isSelected: true } ) ).toBe( true );
		expect( showEditAffordances( { created: true, isSelected: false } ) ).toBe( false );
		expect( showEditAffordances( { created: false, isSelected: true } ) ).toBe( false );
	} );
} );
