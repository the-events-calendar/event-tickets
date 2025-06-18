/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPContainer from './template';
import { selectors } from '../../../data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const getIsDisabled = ( state ) => selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state );

const mapStateToProps = ( state ) => ( {
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
	isDisabled: getIsDisabled( state ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPContainer );
