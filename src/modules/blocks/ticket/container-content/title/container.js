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
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

/**
 * Decodes HTML entities in a given input string and returns the decoded text.
 *
 * @param {string} title - The input string containing HTML entities to be decoded.
 * @returns {string} The decoded text without HTML entities.
 */
export function htmlEntityDecode( title ) {
	const doc = new DOMParser().parseFromString( title, 'text/html' );
	return doc.documentElement.textContent;
}

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	tempTitle: htmlEntityDecode( selectors.getTicketTempTitle( state, ownProps ) ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onTempTitleChange: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTicketTempTitle( clientId, e.target.value ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
