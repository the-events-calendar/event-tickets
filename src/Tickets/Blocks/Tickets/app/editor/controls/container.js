/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import withSaveData from '@moderntribe/tickets/blocks/hoc/with-save-data';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import { applyFilters } from '@wordpress/hooks';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';

const mapStateToProps = (state, ownProps) => {
	const isRecurring = hasRecurrenceRules(state);

	let mappedProps = {
		hasMultipleProviders: selectors.hasMultipleTicketProviders(),
		providers: selectors.getTicketProviders(),
		selectedProvider: selectors.getTicketsProvider(state),
		disabled: false,
	};

	/**
	 * Filters the properties mapped from the state for the Controls component.
	 *
	 * @since TBD
	 *
	 * @param {Object}  mappedProps         The mapped props.
	 * @param {Object}  context.state       The state of the block.
	 * @param {Object}  context.ownProps    The props passed to the block.
	 * @param {boolean} context.isRecurring Whether the current post is a recurring event.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Controls.mappedProps',
		mappedProps,
		{ state, ownProps, isRecurring }
	);

	return mappedProps;
};

const mapDispatchToProps = (dispatch) => ({
	onProviderChange: (e) =>
		dispatch(actions.setTicketsProvider(e.target.name)),
});

export default compose(
	withStore(),
	connect(mapStateToProps, mapDispatchToProps),
	withSaveData()
)(Template);
