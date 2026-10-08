/**
 * Tests for the Block API version adapter.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import renderer from 'react-test-renderer';
import React from 'react';

/**
 * Internal dependencies
 */
import withBlockWrapper, { BLOCK_API_VERSION } from '@moderntribe/tickets/blocks/with-block-wrapper';
import rsvp from '@moderntribe/tickets/blocks/rsvp';
import rsvpV2 from '@moderntribe/tickets/blocks/rsvp-v2';
import rsvpDisabled from '@moderntribe/tickets/blocks/rsvp-disabled';
import attendees from '@moderntribe/tickets/blocks/attendees';

/* Mirrors the className the `useBlockProps` mock in jest.setup.js returns. */
const WRAPPER_CLASS_NAME = 'wp-block';

const Edit = ( { title } ) => <span>{ title }</span>;

const blockDefinition = {
	id: 'tickets-probe',
	title: 'Tickets Probe',
	category: 'tribe-tickets',
	supports: { html: false },
	edit: Edit,
	save: () => null,
};

describe( 'Block wrapper', () => {
	it( 'Should register at Block API version 3', () => {
		/* 3 is the version WordPress 6.9 requires of a block that runs in the iframed editor. */
		expect( BLOCK_API_VERSION ).toBe( 3 );
		expect( withBlockWrapper( blockDefinition ).apiVersion ).toBe( 3 );
	} );

	it.each( [
		[ 'rsvp', rsvp ],
		[ 'rsvp-v2', rsvpV2 ],
		[ 'rsvp-disabled', rsvpDisabled ],
		[ 'attendees', attendees ],
	] )( 'Should register the %s block at Block API version 3', ( name, block ) => {
		expect( withBlockWrapper( block ).apiVersion ).toBe( BLOCK_API_VERSION );
	} );

	it( 'Should leave the rest of the block definition untouched', () => {
		const wrapped = withBlockWrapper( blockDefinition );
		const untouched = Object.keys( blockDefinition ).filter( ( key ) => key !== 'edit' );

		untouched.forEach( ( key ) => {
			expect( wrapped[ key ] ).toEqual( blockDefinition[ key ] );
		} );
	} );

	it( 'Should render the block edit component inside the wrapper element', () => {
		const wrapped = withBlockWrapper( blockDefinition );
		const component = renderer.create( <wrapped.edit title={ blockDefinition.title } /> );

		expect( component.root.findByType( Edit ).props.title ).toBe( blockDefinition.title );
		expect( component.toJSON() ).toMatchObject( {
			type: 'div',
			props: { className: WRAPPER_CLASS_NAME },
		} );
	} );

	it( 'Should not wrap a block that already renders its own wrapper', () => {
		const migrated = { ...blockDefinition, apiVersion: 2 };

		expect( withBlockWrapper( migrated ) ).toBe( migrated );
	} );
} );
