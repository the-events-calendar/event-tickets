/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPContainerHeader from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	available: selectors.getRSVPAvailable( state ),
	description: selectors.getRSVPDescription( state ),
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
	isCreated: selectors.getRSVPCreated( state ),
	title: selectors.getRSVPTitle( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	setAddEditOpen: () => dispatch( actions.setRSVPIsAddEditOpen( true ) ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( RSVPContainerHeader );
