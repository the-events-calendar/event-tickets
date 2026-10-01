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
import { plugins } from '@moderntribe/common/data';
import { globals } from '@moderntribe/common/utils';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
	hasIacVars: ! isEmpty( globals.iacVars() ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPContainerContent );
