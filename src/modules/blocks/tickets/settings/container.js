/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import TicketsSettingsDashboard from './template';

import { actions } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapDispatchToProps = ( dispatch ) => ( {
	onCloseClick: () => dispatch( actions.closeSettings() ),
} );

export default compose(
	withStore(),
	connect( null, mapDispatchToProps ),
)( TicketsSettingsDashboard );

