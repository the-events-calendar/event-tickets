import React from 'react';
import { addFilter, removeFilter } from '@wordpress/hooks';

/*
 * The real checkbox, label and date picker come from common, whose own copy of React breaks hooks under Jest;
 * stand-ins that keep their props are enough to see what the template renders.
 */
jest.mock( '@moderntribe/common/elements', () => ( {
	Checkbox: () => null,
	DayPickerInput: () => null,
	LabeledItem: () => null,
} ) );

// The template reads its labels from the editor config as it is imported.
window.tribe_editor_config = {
	tickets: { salePrice: { add_sale_price: 'Add Sale Price', on_sale_from: 'On sale from', to: 'to' } },
};

const { DayPickerInput } = require( '@moderntribe/common/elements' );
const SalePrice = require( '../../src/Tickets/Blocks/Ticket/app/editor/container-content/sale-price/template' ).default;

const HOOK = 'tec.tickets.blocks.Ticket.SalePrice.renderPickers';
const NAMESPACE = 'tests/relative-sale-dates/sale-price';
const CLIENT_ID = 'ticket-block-sale-price';
const DATES_ROW_CLASS = 'tribe-editor__ticket__sale-price--dates';

/**
 * Renders the sale price section of a ticket block with its sale price checked.
 *
 * @return {Object} The test renderer's root instance.
 */
function renderSalePrice() {
	return global.renderer.create(
		<SalePrice clientId={ CLIENT_ID } salePriceChecked validSalePrice fromDateInput="from" toDateInput="to" />
	).root;
}

describe( 'the Ticket block sale price section', () => {
	beforeEach( () => {
		window.__tribe_common_store__ = {
			getState: () => ( { tickets: { blocks: { ticket: { provider: 'TEC\\Tickets\\Commerce\\Module' } } } } ),
		};
	} );

	afterEach( () => {
		removeFilter( HOOK, NAMESPACE );
		delete window.__tribe_common_store__;
	} );

	it( 'should render the dates row with both date pickers when nothing filters it', () => {
		const root = renderSalePrice();
		const [ row ] = root.findAll( ( node ) => DATES_ROW_CLASS === node.props.className );
		const pickers = row.findAllByType( DayPickerInput );

		expect( pickers.map( ( picker ) => picker.props.value ) ).toStrictEqual( [ 'from', 'to' ] );
	} );

	it( 'should render what tec.tickets.blocks.Ticket.SalePrice.renderPickers returns in place of the dates row', () => {
		const Replacement = () => null;
		addFilter( HOOK, NAMESPACE, () => <Replacement /> );

		const root = renderSalePrice();

		expect( root.findAllByType( Replacement ) ).toHaveLength( 1 );
		expect( root.findAll( ( node ) => DATES_ROW_CLASS === node.props.className ) ).toHaveLength( 0 );
		expect( root.findAllByType( DayPickerInput ) ).toHaveLength( 0 );
	} );

	it( 'should pass tec.tickets.blocks.Ticket.SalePrice.renderPickers the dates row, the client ID and both pickers', () => {
		const filter = jest.fn( ( row ) => row );
		addFilter( HOOK, NAMESPACE, filter );

		renderSalePrice();

		const [ row, clientId, pickers ] = filter.mock.calls[ 0 ];
		expect( row.props.className ).toBe( DATES_ROW_CLASS );
		expect( clientId ).toBe( CLIENT_ID );
		expect( pickers.start.type ).toBe( DayPickerInput );
		expect( pickers.start.props.value ).toBe( 'from' );
		expect( pickers.end.type ).toBe( DayPickerInput );
		expect( pickers.end.props.value ).toBe( 'to' );
	} );
} );
