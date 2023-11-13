import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';
import Uneditable from './template';

const mocks = {
	tickets: [
		{
			type: 'series_pass',
			title: 'Series Pass One',
			description: 'This is a description for Series Pass One',
			capacityType: 'unlimited',
			price: 23.00,
			capacity: 100,
			available: 89,
		},
		{
			type: 'series_pass',
			title: 'Series Pass Two',
			description: 'This is a description for Series Pass Two',
			capacityType: 'global',
			price: 89.00,
			capacity: 200,
			available: 12,

			// updated mock data
			sharedCapacity: 150,
			sold: 5,
			sharedSold: 3,
			isShared: true,
			currencyDecimalPoint: '.',
			currencyNumberOfDecimals: 2,
			currencyPosition: 'prefix',
			currencySymbol: '$',
			currencyThousandsSep: ',',
		},
	],
	cardsByTicketType: {
		series_pass: {
			title: 'Series Passes',
			noticeHtml: 'This event is part of a Series ...', // This will be sanitized HTML, to be dang. set.
			link: 'https://example.com',
		},
	},
};

const mapStateToProps = (state, ownProps) => ({
	tickets: mocks.tickets,
	cardsByTicketType: mocks.cardsByTicketType,
});

export default compose(withStore(), connect(mapStateToProps))(Uneditable);
