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
import withSaveData from '../../../../../../modules/blocks/hoc/with-save-data';
import { selectors, actions } from '../../../../../../modules/data/blocks/ticket';
import { applyFilters } from '@wordpress/hooks';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';

export const mapStateToProps = ( state, ownProps ) => {
	const isRecurring = hasRecurrenceRules( state );

	let mappedProps = {
		disabled: false,
		hasMultipleProviders: selectors.hasMultipleTicketProviders(),
		message: null,
		providers: selectors.getTicketProviders(),
		selectedProvider: selectors.getTicketsProvider( state ),
		hasTickets: selectors.hasCreatedTickets( state ),
	};

	/**
	 * Filters the properties mapped from the state for the Controls component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object}  mappedProps         The mapped props.
	 * @param {Object}  context.state       The state of the block.
	 * @param {Object}  context.ownProps    The props passed to the block.
	 * @param {boolean} context.isRecurring Whether the current post is a recurring event.
	 */
	mappedProps = applyFilters( 'tec.tickets.blocks.Tickets.Controls.mappedProps', mappedProps, {
		state,
		ownProps,
		isRecurring,
	} );

	return mappedProps;
};

const mapDispatchToProps = ( dispatch ) => ( {
	onProviderChange: ( e ) => dispatch( actions.setTicketsProvider( e.target.name ) ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ), withSaveData() )( Template );
