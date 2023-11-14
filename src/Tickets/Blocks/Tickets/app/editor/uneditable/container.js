import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';
import Uneditable from './template';
import { isTicketEditableFromPost } from '@moderntribe/tickets/data/blocks/ticket/utils';
import { memo } from 'react';

const mocks = {
	cardsByTicketType: {
		series_pass: {
			title: 'Series Passes',
			noticeHtml: 'This event is part of a Series ...', // This will be sanitized HTML, to be dang. set.
			link: 'https://example.com',
		},
	},
};

const getUneditableTickets = (ownProps) => {
	const currentPost = wp.data.select('core/editor').getCurrentPost();
	return ownProps.tickets.filter((ticket) => {
		return !isTicketEditableFromPost(
			ticket.id,
			ticket.type || 'default',
			currentPost
		);
	});
};

const mapStateToProps = (state, ownProps) => ({
	tickets: getUneditableTickets(ownProps),
	cardsByTicketType: mocks.cardsByTicketType,
});

// Safe to memoize the component as its properties will only be set once.
export default memo(compose(withStore(), connect(mapStateToProps))(Uneditable));
