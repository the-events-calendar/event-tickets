/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPAdvancedOptions from './template';
import { selectors } from '../../../data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const getIsDisabled = ( state ) => selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state );

const mapStateToProps = ( state ) => ( {
	isDisabled: getIsDisabled( state ),
	hasBeenCreated: selectors.getRSVPCreated( state ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPAdvancedOptions );
