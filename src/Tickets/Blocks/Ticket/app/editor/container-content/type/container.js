/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { plugins } from '@moderntribe/common/data';
import { applyFilters } from '@wordpress/hooks';

const mapStateToProps = (state, ownProps) => {
	const postType = select('core/editor').getCurrentPostType();
	const ticketDetails = selectors.getTicketDetails(state, ownProps);
	const typeDescription = ticketDetails.typeDescription;

	let mappedProps = {
		hasEventsPro: plugins.selectors.hasPlugin(state)(
			plugins.constants.EVENTS_PRO_PLUGIN
		),
		postType,
		typeDescription,
		typeIconUrl: ticketDetails.typeIconUrl,
		typeName: ticketDetails.typeName,
	};

	/**
	 * Filters the properties mapped from the state for the Ticket Type component.
	 *
	 * @since TBD
	 *
	 * @type {Object} mappedProps The properties mapped from the state for the Ticket Type component.
	 * @param {Object} state    The current state.
	 * @param {Object} ownProps The component props.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.ticket.Type.mappedProps',
		mappedProps,
		state,
		ownProps,
		ticketDetails
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
