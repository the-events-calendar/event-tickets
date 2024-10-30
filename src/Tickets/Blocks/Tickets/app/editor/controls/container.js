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
import withSaveData from '@moderntribe/tickets/blocks/hoc/with-save-data';
import {
	selectors,
	actions,
	constants,
} from '@moderntribe/tickets/data/blocks/ticket';
import { applyFilters } from '@wordpress/hooks';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import { __ } from '@wordpress/i18n';

const { TICKET_LABELS } = constants;

const mapStateToProps = (state, ownProps) => {
	const isRecurring = hasRecurrenceRules(state);
	// eslint-disable-next-line no-undef
	const message = sprintf(
		/* Translators: %s - the plural, lowercase label for a ticket. */
		__(
			'It looks like you have multiple ecommerce plugins active. We recommend running only one at a time. However, if you need to run multiple, please select which one to use to sell %s for this event. ', // eslint-disable-line max-len
			'event-tickets'
		),
		TICKET_LABELS.ticket.pluralLowercase
	);

	// eslint-disable-next-line no-undef
	const note = sprintf(
		/* Translators: %1$s - the plural, lowercase label for a ticket; %2$s - the plural, lowercase label for a ticket; %3$s - the plural, lowercase label for a ticket. */
		__(
			'Note: adjusting this setting will only impact new %1$s. Existing %2$s will not change. We highly recommend that all %3$s for one event use the same ecommerce plugin.', // eslint-disable-line max-len
			'event-tickets'
		),
		TICKET_LABELS.ticket.pluralLowercase,
		TICKET_LABELS.ticket.pluralLowercase,
		TICKET_LABELS.ticket.pluralLowercase
	);
	const messageElement = (
		<p>
			{message}
			{<em>{note}</em>}
		</p>
	);

	let mappedProps = {
		disabled: false,
		hasMultipleProviders: selectors.hasMultipleTicketProviders(),
		message: messageElement,
		providers: selectors.getTicketProviders(),
		selectedProvider: selectors.getTicketsProvider(state),
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
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Controls.mappedProps',
		mappedProps,
		{ state, ownProps, isRecurring }
	);

	return mappedProps;
};

const mapDispatchToProps = (dispatch) => ({
	onProviderChange: (e) =>
		dispatch(actions.setTicketsProvider(e.target.name)),
});

export default compose(
	withStore(),
	connect(mapStateToProps, mapDispatchToProps),
	withSaveData()
)(Template);
