/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state, ownProps ) => {
	const selectedBlock = select( 'core/editor' ).getSelectedBlock();

	return ( {
		isBlockSelected: selectedBlock?.name === 'tribe/tickets',
	} );
};

export default compose(withStore(), connect(mapStateToProps))(Template);
