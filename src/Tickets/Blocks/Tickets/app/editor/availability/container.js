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
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { applyFilters } from '@wordpress/hooks';

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		total: selectors.getIndependentAndSharedTicketsCapacity(state),
		available: selectors.getIndependentAndSharedTicketsAvailable(state),
	};

	/**
	 * Filters the properties mapped from the state for the Availability component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps      The mapped props.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Availability.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
