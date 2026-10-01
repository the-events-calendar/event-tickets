/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPV2Capacity from './template';
import { withStore } from '@moderntribe/common/hoc';
import { actions, selectors } from '../../../data/blocks/rsvp-v2';
import { schedulePersistRSVP } from '../utils/schedule-persist-rsvp';

const mapStateToProps = ( state ) => ( {
	isDisabled: selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state ),
	tempCapacity: selectors.getRSVPTempCapacity( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onTempCapacityChange: ( e ) => {
		dispatch( actions.setRSVPTempCapacity( e.target.value ) );
		dispatch( actions.setRSVPHasChanges( true ) );
		schedulePersistRSVP( dispatch );
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPV2Capacity );
