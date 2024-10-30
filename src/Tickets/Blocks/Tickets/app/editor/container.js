/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import withSaveData from '@moderntribe/tickets/blocks/hoc/with-save-data';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import {
	hasRecurrenceRules,
	noTicketsOnRecurring,
} from '@moderntribe/common/utils/recurrence';

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		Warning: null,
		canCreateTickets: selectors.canCreateTickets(),
		hasRecurrenceRules: hasRecurrenceRules(state),
		isSettingsOpen: selectors.getTicketsIsSettingsOpen(state),
		noTicketsOnRecurring: noTicketsOnRecurring(),

		/**
		 * These properties are not required to render the template, but are
		 * required by the `withSaveData` HOC to spot changes in the block.
		 */
		hasProviders: selectors.hasTicketProviders(),
		provider: selectors.getTicketsProvider(state),
		sharedCapacity: selectors.getTicketsSharedCapacity(state),
	};

	/**
	 * Filters the properties mapped from the state for the Tickets component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps      The mapped props.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

const mapDispatchToProps = (dispatch) => ({
	setInitialState: (props) => {
		dispatch(actions.setTicketsInitialState(props));
	},
	onBlockUpdate: (isSelected) => {
		dispatch(actions.setTicketsIsSelected(isSelected));
	},
	onBlockRemoved: () => {
		dispatch(actions.resetTicketsBlock());
	},
});

export default compose(
	withStore(),
	connect(mapStateToProps, mapDispatchToProps),
	withSaveData()
)(Template);
