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

const mapStateToProps = ( state, ownProps ) => {
	const postType = select( 'core/editor' ).getCurrentPostType();
	const ticketDetails = selectors.getTicketDetails( state, ownProps );

	return ( {
		postType: postType.replace( 'tribe_events', 'event' ),
		type: ticketDetails.type,
		seriesTitle: 'TODO this',
	}
) };

export default compose(
	withStore(),
	connect(
		mapStateToProps,
	),
)( Template );
