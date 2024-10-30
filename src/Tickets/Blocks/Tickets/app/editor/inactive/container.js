import { connect } from 'react-redux';
import { compose } from 'redux';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';
import { applyFilters } from '@wordpress/hooks';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import Template from './template';

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		allTicketsFuture: selectors.allTicketsFuture(state),
		allTicketsPast: selectors.allTicketsPast(state),
		canCreateTickets: selectors.canCreateTickets(),
		hasCreatedTickets: selectors.hasCreatedTickets(state),
		hasRecurrenceRules: hasRecurrenceRules(state),
		showWarning: false,
		Warning: null,
		postTypeLabel: selectors
			.getCurrentPostTypeLabel('singular_name')
			.toLowerCase(),
	};

	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Inactive.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
