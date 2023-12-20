/**
 * Internal dependencies
 */
import TicketBlock from '@moderntribe/tickets/blocks/ticket';

jest.mock( '@moderntribe/common/utils/globals', () => ( {
	dateSettings: () => {},
	iacVars: () => {
		return {
			iacDefault: 'none',
		};
	},
	priceSettings: () => {
		return {
			defaultCurrencyPosition: true,
		};
	},
	settings: () => {
		return {
			reverseCurrencyPosition: false,
		};
	},
	tecDateSettings: () => {
		return {
			datepickerFormat: '',
		};
	},
	tickets: () => {
		return {
			end_sale_buffer_duration: 2,
		};
	},
	wpHooks: {
		addFilter: () => {},
	},
	wpEditor: {
		MediaUpload: () => ( <button>Media Upload</button> ),
	},
} ) );

describe( 'Single ticket block declaration', () => {
	test( 'Block declaration', () => {
		expect( TicketBlock ).toMatchSnapshot();
	} );
} );
