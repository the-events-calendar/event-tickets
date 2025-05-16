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
import { actions, selectors } from '../../../data/blocks/rsvp';

const mapStateToProps = ( state ) => ( {
	isDisabled: selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state ),
	tempTitle: selectors.getRSVPTempTitle( state ),
	title: selectors.getRSVPTitle( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onTempTitleChange: ( e ) => {
		dispatch( actions.setRSVPTempTitle( e.target.value ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( Template );
