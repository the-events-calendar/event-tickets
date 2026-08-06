/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPSidebarControls from './template';
import { withStore } from '@moderntribe/common/hoc';
import { actions, selectors, thunks } from '../../../data/blocks/rsvp-v2';
import { plugins } from '@moderntribe/common/data';

const mapStateToProps = ( state ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	isLoading: selectors.getRSVPIsLoading( state ),
	notGoingResponses: selectors.getRSVPNotGoingResponses( state ),
	showAttendees: selectors.getRSVPShowAttendees( state ),
	state,
} );

const mapDispatchToProps = ( dispatch ) => ( {
	dispatch,
} );

const mergeProps = ( stateProps, dispatchProps ) => {
	const { state, isLoading, ...restStateProps } = stateProps;
	const { dispatch } = dispatchProps;

	return {
		...restStateProps,
		isLoading,
		onToggleNotGoing: ( checked ) => {
			if ( isLoading || selectors.getRSVPNotGoingResponses( state ) === checked ) {
				return;
			}

			dispatch( actions.setRSVPNotGoingResponses( checked ) );
			dispatch( actions.setRSVPTempNotGoingResponses( checked ) );
			dispatch( thunks.persistRSVP( { notGoingResponses: checked } ) );
		},
		onToggleShowAttendees: ( checked ) => {
			if ( isLoading || selectors.getRSVPShowAttendees( state ) === checked ) {
				return;
			}

			dispatch( actions.setRSVPShowAttendees( checked ) );
			dispatch( actions.setRSVPTempShowAttendees( checked ) );
			dispatch( thunks.persistRSVP( { showAttendees: checked } ) );
		},
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps, mergeProps )
)( RSVPSidebarControls );
