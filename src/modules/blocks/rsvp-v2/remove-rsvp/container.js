/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { dispatch as wpDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPRemoveRsvp from './template';
import { actions, selectors, thunks } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';

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
				! window.confirm(
					// eslint-disable-line no-alert
					__( 'Are you sure you want to remove RSVP? This cannot be undone.', 'event-tickets' )
				)
			) {
				return;
			}

			if ( stateProps.created && stateProps.rsvpId ) {
				await dispatch( thunks.deleteRSVP( stateProps.rsvpId ) );
			}

			dispatch( actions.deleteRSVP() );
			wpDispatch( 'core/block-editor' ).removeBlocks( [ ownProps.clientId ] );
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
