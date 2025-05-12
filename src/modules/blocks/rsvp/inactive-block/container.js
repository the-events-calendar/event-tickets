/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPInactiveBlock from './template';
import { actions, selectors } from '../../../data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	created: selectors.getRSVPCreated( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	setAddEditOpen: () => dispatch( actions.setRSVPIsAddEditOpen( true ) ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPInactiveBlock );
