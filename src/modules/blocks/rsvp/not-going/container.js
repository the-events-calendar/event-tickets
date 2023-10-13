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
import { actions, selectors } from '@moderntribe/tickets/data/blocks/rsvp';

const mapStateToProps = ( state ) => ( {
	tempNotGoingResponses: selectors.getRSVPTempNotGoingResponses( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onTempNotGoingResponsesChange: ( e ) => {
		dispatch( actions.setRSVPTempNotGoingResponses( e.target.checked ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
