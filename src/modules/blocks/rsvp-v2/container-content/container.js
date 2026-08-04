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
import { withStore } from '@moderntribe/common/hoc';
import { globals } from '@moderntribe/common/utils';
import { selectors } from '../../../data/blocks/rsvp-v2';
import RSVPContainerContent from './template';

const mapStateToProps = ( state ) => ( {
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
	hasIacVars: ! isEmpty( globals.iacVars() ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPContainerContent );
