/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import SettingsActionButton from './template';

import { actions } from '../../../../../../../modules/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapDispatchToProps = ( dispatch ) => ( {
	onClick: () => dispatch( actions.openSettings() ),
} );

export default compose( withStore(), connect( null, mapDispatchToProps ) )( SettingsActionButton );
