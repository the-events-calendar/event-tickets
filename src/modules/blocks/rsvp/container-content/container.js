/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPContainerContent from './template';
import { selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { plugins } from '@moderntribe/common/data';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( RSVPContainerContent );
