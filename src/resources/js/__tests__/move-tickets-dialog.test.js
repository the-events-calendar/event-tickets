/**
 * The move dialog, opened in a frame over a post that defers ticket saves, stages the move in the
 * parent window instead of moving the ticket over AJAX.
 */
import fs from 'fs';
import path from 'path';
import $ from 'jquery';

const source = fs.readFileSync( path.join( __dirname, '..', 'move-tickets-dialog.js' ), 'utf8' );

const load = () => {
	window.jQuery = $;
	delete window.tribe_move_tickets;
	// A classic script: its `var` namespace is a window global, as when WordPress prints it.
	window.eval( source );

	return window.tribe_move_tickets;
};

const parentWith = ( deferredSave ) => ( { tribe: { tickets: { deferredSave } } } );

describe( 'move dialog over a post that defers ticket saves', () => {
	it( 'stages the move in the parent and says so', () => {
		const stageMove = jest.fn( () => true );
		const parent = parentWith( { isEnabled: () => true, stageMove, strings: { moveStaged: 'Staged.' } } );

		const outcome = load().stage_move_in_parent( parent, 12, 99, 'Other event' );

		expect( stageMove ).toHaveBeenCalledWith( 12, 99, 'Other event' );
		expect( outcome ).toEqual( { staged: true, message: 'Staged.' } );
	} );

	it( 'reports a move the parent refuses because the ticket has a staged edit', () => {
		const parent = parentWith( {
			isEnabled: () => true,
			stageMove: () => false,
			strings: { moveBlocked: 'Save the post first.' },
		} );

		expect( load().stage_move_in_parent( parent, 12, 99, 'Other event' ) ).toEqual( {
			staged: false,
			message: 'Save the post first.',
		} );
	} );

	it( 'leaves the move to the AJAX request when the parent cannot be read or does not defer saves', () => {
		const crossOrigin = {
			get tribe() {
				throw new Error( 'Blocked a frame from accessing a cross-origin frame.' );
			},
		};
		const dialog = load();

		expect( dialog.stage_move_in_parent( crossOrigin, 12, 99, '' ) ).toBeNull();
		expect( dialog.stage_move_in_parent( parentWith( { isEnabled: () => false } ), 12, 99, '' ) ).toBeNull();
		expect( dialog.stage_move_in_parent( {}, 12, 99, '' ) ).toBeNull();
		expect( dialog.stage_move_in_parent( null, 12, 99, '' ) ).toBeNull();
	} );
} );
