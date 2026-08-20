/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import { withStore } from '@moderntribe/common/hoc';
import { actions, selectors, thunks } from '../../../data/blocks/rsvp-v2';
import RSVPRemoveRsvp from './template';

const mapStateToProps = ( state ) => ( {
	created: selectors.getRSVPCreated( state ),
	isDisabled: selectors.getRSVPSettingsOpen( state ),
	isLoading: selectors.getRSVPIsLoading( state ),
	rsvpId: selectors.getRSVPId( state ),
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { dispatch } = dispatchProps;

	return {
		...ownProps,
		isDisabled: stateProps.isDisabled,
		isLoading: stateProps.isLoading,
		onRemove: async () => {
			if (
				// eslint-disable-next-line no-alert
				! window.confirm(
					__( 'Are you sure you want to remove RSVP? This cannot be undone.', 'event-tickets' )
				)
			) {
				return;
			}

			if ( stateProps.created && stateProps.rsvpId ) {
				await dispatch( thunks.deleteRSVP( stateProps.rsvpId ) );
			}

			dispatch( actions.deleteRSVP() );
			dispatch( actions.setRSVPIsInitializing( false ) );
		},
	};
};

const ConnectedRemoveRsvp = compose( withStore(), connect( mapStateToProps, null, mergeProps ) )( RSVPRemoveRsvp );

const RSVPRemoveRsvpContainer = ( { clientId, isSelected, created } ) => {
	if ( ! created || ! isSelected ) {
		return null;
	}

	return <ConnectedRemoveRsvp clientId={ clientId } />;
};

export default RSVPRemoveRsvpContainer;
