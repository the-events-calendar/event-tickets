/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import RSVPContainerContent from './template';
import { selectors } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
	hasIacVars: ! isEmpty( globals.iacVars() ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPContainerContent );
