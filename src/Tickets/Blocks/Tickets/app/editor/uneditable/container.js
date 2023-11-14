import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';
import Uneditable from './template';
import { isTicketEditableFromPost } from '@moderntribe/tickets/data/blocks/ticket/utils';
import { memo } from 'react';
import { tickets } from '@moderntribe/common/utils/globals';

const mocks = {
	cardsByTicketType: {
		series_pass: {
			title: 'Series Passes',
			noticeHtml: 'This event is part of a Series ...', // This will be sanitized HTML, to be dang. set.
			link: 'https://example.com',
		},
	},
};

const getUneditableTickets = (ticketsArray) => {
	const currentPost = wp.data.select('core/editor').getCurrentPost();
	return ticketsArray.filter((ticket) => {
		return !isTicketEditableFromPost(
			ticket.id,
			ticket.type || 'default',
			currentPost
		);
	});
};

const getCardsByTicketType = (ticketsArray) => {
	const cardsByTicketType = tickets()?.ticketTypes || {};
	const ticketTypes = ticketsArray.reduce((acc, ticket) => {
		if (acc.indexOf(ticket.type) === -1) {
			acc.push(ticket.type);
		}

		return acc;
	}, []);
	ticketTypes.forEach((type) => {
		cardsByTicketType[type] = cardsByTicketType[type] || {
			title: type,
		};
	});
	return cardsByTicketType;
};

const mapStateToProps = (state, ownProps) => ({
	tickets: getUneditableTickets(ownProps?.tickets || []),
	cardsByTicketType: getCardsByTicketType(ownProps?.tickets || []),
});

// Safe to memoize the component as its properties will only be set once.
export default memo(compose(withStore(), connect(mapStateToProps))(Uneditable));
