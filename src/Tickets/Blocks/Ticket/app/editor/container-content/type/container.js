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
	let typeDescription = ticketDetails.typeDescription;

	/**
	 * Filters the ticket type description.
	 *
	 * @since TBD
	 *
	 * @param {string} typeDescription The ticket type description.
	 * @param {Object} ticketDetails   The ticket details.
	 * @param {string} postType        The post type.
	 */
	typeDescription = applyFilters(
		'tec.tickets.blocks.ticket.typeDescription',
		typeDescription,
		ticketDetails,
		postType
	);

	return {
		hasEventsPro: plugins.selectors.hasPlugin( state )( plugins.constants.EVENTS_PRO_PLUGIN ),
		postType,
		typeDescription,
		typeIconUrl: ticketDetails.typeIconUrl,
		typeName: ticketDetails.typeName,
	};
};

export default compose(withStore(), connect(mapStateToProps))(Template);
