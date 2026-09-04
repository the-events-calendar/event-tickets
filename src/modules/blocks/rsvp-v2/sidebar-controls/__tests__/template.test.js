/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import RSVPSidebarControls from '../template';

const findToggle = ( tree, label ) =>
	tree.findAll( ( node ) => node.props && node.props.label === label, { deep: true } )[ 0 ];

const noop = () => {};

describe( 'RSVPSidebarControls', () => {
	it( 'does not render the show-attendees toggle when Event Tickets Plus is inactive', () => {
		const component = renderer.create(
			<RSVPSidebarControls
				hasTicketsPlus={ false }
				notGoingResponses={ false }
				onToggleNotGoing={ noop }
				onToggleShowAttendees={ noop }
				showAttendees={ false }
			/>
		);

		expect( findToggle( component.root, 'Show attendees list on Event page' ) ).toBeUndefined();
	} );

	it( 'renders the show-attendees toggle when Event Tickets Plus is active', () => {
		const component = renderer.create(
			<RSVPSidebarControls
				hasTicketsPlus={ true }
				notGoingResponses={ false }
				onToggleNotGoing={ noop }
				onToggleShowAttendees={ noop }
				showAttendees={ true }
			/>
		);

		const toggle = findToggle( component.root, 'Show attendees list on Event page' );

		expect( toggle ).toBeDefined();
		expect( toggle.props.checked ).toBe( true );
	} );

	it( 'calls onToggleShowAttendees when the toggle is switched', () => {
		const onToggleShowAttendees = jest.fn();
		const component = renderer.create(
			<RSVPSidebarControls
				hasTicketsPlus={ true }
				notGoingResponses={ false }
				onToggleNotGoing={ noop }
				onToggleShowAttendees={ onToggleShowAttendees }
				showAttendees={ false }
			/>
		);

		const toggle = findToggle( component.root, 'Show attendees list on Event page' );
		toggle.props.onChange( true );

		expect( onToggleShowAttendees ).toHaveBeenCalledWith( true );
	} );
} );
