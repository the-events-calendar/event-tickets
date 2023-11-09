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
	let postType = select( 'core/editor' ).getCurrentPostType();
	postType = postType.replace( 'tribe_events', 'event' );
	const ticketDetails = selectors.getTicketDetails( state, ownProps );

	return ( {
		postType,
		type: ticketDetails.type,
		typeDescription: ticketDetails.typeDescription,
		typeIconUrl: ticketDetails.typeIconUrl,
		typeName: ticketDetails.typeName,
	}
) };

export default compose(
	withStore(),
	connect(
		mapStateToProps,
	),
)( Template );
