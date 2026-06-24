/**
 * V2 RSVP Inactive Block Container
 *
 * Opens the create form when the user clicks "Add RSVP".
 */

/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPInactiveBlock from '../../rsvp/inactive-block/template';
import { actions, selectors } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	created: selectors.getRSVPCreated( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	setAddEditOpen: () => dispatch( actions.setRSVPIsAddEditOpen( true ) ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPInactiveBlock );
