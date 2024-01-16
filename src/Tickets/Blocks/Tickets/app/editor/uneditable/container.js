import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';
import Uneditable from './template';
import { tickets } from '@moderntribe/common/utils/globals';
import { applyFilters } from '@wordpress/hooks';

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
			description: '',
		};
	});
	return cardsByTicketType;
};

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		cardsByTicketType: getCardsByTicketType(ownProps?.tickets || []),
	};

	/**
	 * Filters the properties mapped from the state for the Uneditable component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps      The mapped props.
	 * @param {Object} context.state    The store state the properties are mapped from.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Uneditable.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Uneditable);
