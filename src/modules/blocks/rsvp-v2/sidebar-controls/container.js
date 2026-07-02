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

const mapStateToProps = ( state ) => ( {
	isLoading: selectors.getRSVPIsLoading( state ),
	notGoingResponses: selectors.getRSVPNotGoingResponses( state ),
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
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps, mergeProps )
)( RSVPSidebarControls );
