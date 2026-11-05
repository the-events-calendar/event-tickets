import React from 'react';
import { addFilter, removeFilter } from '@wordpress/hooks';

/*
 * The real picker and label come from common, whose own copy of React breaks hooks under Jest; stand-ins that keep
 * their props are enough to see what the template renders.
 */
jest.mock( '../../src/modules/elements', () => ( {
	DateTimeRangePicker: () => null,
	LabelWithTooltip: () => null,
} ) );

const { DateTimeRangePicker } = require( '../../src/modules/elements' );
const TicketDuration = require( '../../src/Tickets/Blocks/Ticket/app/editor/container-content/duration/template' ).default;

const HOOK = 'tec.tickets.blocks.Ticket.Duration.renderPicker';
const NAMESPACE = 'tests/relative-sale-dates/duration';
const CLIENT_ID = 'ticket-block-duration';

/**
 * Renders the Sale Duration section of a ticket block.
 *
 * @return {Object} The test renderer's root instance.
 */
function renderDuration() {
	return global.renderer.create( <TicketDuration clientId={ CLIENT_ID } fromTime="10:00" toTime="18:00" /> ).root;
}

describe( 'the Ticket block Sale Duration', () => {
	afterEach( () => {
		removeFilter( HOOK, NAMESPACE );
	} );

	it( 'should render the date and time range picker with the section props when nothing filters it', () => {
		const picker = renderDuration().findByType( DateTimeRangePicker );

		expect( picker.props.className ).toBe( 'tribe-editor__ticket__duration-picker' );
		expect( picker.props.fromTime ).toBe( '10:00' );
		expect( picker.props.toTime ).toBe( '18:00' );
	} );

	it( 'should render what tec.tickets.blocks.Ticket.Duration.renderPicker returns in place of the picker', () => {
		const Replacement = () => null;
		addFilter( HOOK, NAMESPACE, () => <Replacement /> );

		const root = renderDuration();

		expect( root.findAllByType( Replacement ) ).toHaveLength( 1 );
		expect( root.findAllByType( DateTimeRangePicker ) ).toHaveLength( 0 );
	} );

	it( 'should pass tec.tickets.blocks.Ticket.Duration.renderPicker the picker and the client ID of the ticket block', () => {
		const filter = jest.fn( ( picker ) => picker );
		addFilter( HOOK, NAMESPACE, filter );

		renderDuration();

		const [ picker, clientId ] = filter.mock.calls[ 0 ];
		expect( picker.type ).toBe( DateTimeRangePicker );
		expect( picker.props.fromTime ).toBe( '10:00' );
		expect( clientId ).toBe( CLIENT_ID );
	} );
} );
