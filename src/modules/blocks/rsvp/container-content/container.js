/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPContainerContent from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const getIsDisabled = ( state ) => (
	selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state )
);

const mapStateToProps = ( state ) => ( {
	isDisabled: getIsDisabled( state ),
	tempCapacity: selectors.getRSVPTempCapacity( state ),
	tempNotGoingResponses: selectors.getRSVPTempNotGoingResponses( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onTempCapacityChange: ( e ) => {
		dispatch( actions.setRSVPTempCapacity( e.target.value ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	},
	onTempNotGoingResponsesChange: ( e ) => {
		dispatch( actions.setRSVPTempNotGoingResponses( e.target.checked ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	}
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( RSVPContainerContent );
