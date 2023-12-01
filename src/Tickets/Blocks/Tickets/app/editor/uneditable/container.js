import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';
import Uneditable from './template';
import { tickets } from '@moderntribe/common/utils/globals';

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
	cardsByTicketType: getCardsByTicketType(ownProps?.tickets || []),
});

export default compose(withStore(), connect(mapStateToProps))(Uneditable);
