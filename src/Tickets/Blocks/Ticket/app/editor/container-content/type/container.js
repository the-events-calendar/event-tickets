/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { plugins } from '@moderntribe/common/data';
import { applyFilters } from '@wordpress/hooks';
import { Ticket as TicketIcon } from '@moderntribe/tickets/icons';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

const mapStateToProps = (state, ownProps) => {
	const postTypeLabel = selectors
		.getCurrentPostTypeLabel('singular_name')
		.toLowerCase();
	const ticketDetails = selectors.getTicketDetails(state, ownProps);
	const typeName = sprintf(
		/* Translators: %s - the singular label for a ticket. */
		_x('Standard %s', 'Default ticket type label.', 'event-tickets'),
		TICKET_LABELS.ticket.singular
	);
	const typeDescription = sprintf(
		// translators: %s is the post type name in human readable form.
		_x(
			'A standard ticket is specific to this %s.',
			'Default ticket type description.',
			'event-tickets'
		),
		postTypeLabel
	);
	const hasEventsPro = plugins.selectors.hasPlugin(state)(
		plugins.constants.EVENTS_PRO_PLUGIN
	);
	const currentPostIsEvent = selectors.currentPostIsEvent();

	// Show an ECP related upsell message if on an Event and the user doesn't have ECP activated.
	const upsellMessage =
		!hasEventsPro && currentPostIsEvent
			? sprintf(
					/* Translators: %s - the singular label for a ticket. */
					_x(
						'For more %s types, <a href="https://evnt.is/tt-ecp" target="_blank" rel="noopener noreferrer">upgrade</a> to Events Calendar Pro',
						'Default ticket type upsell description.',
						'event-tickets'
					),
					TICKET_LABELS.ticket.singularLowercase
			  )
			: null;
	const typeIcon = <TicketIcon />;

	let mappedProps = {
		typeName,
		typeDescription,
		upsellMessage,
		typeIcon,
	};

	/**
	 * Filters the properties mapped from the state for the Ticket Type component.
	 *
	 * @since 5.8.0
	 *
	 * @type {Object} mappedProps The properties mapped from the state for the Ticket Type component.
	 * @type {Object} context.state The current state.
	 * @type {Object} context.ownProps The properties passed to the component.
	 * @type {Object} context.ticketDetails The ticket details.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.Type.mappedProps',
		mappedProps,
		{
			state,
			ownProps,
			ticketDetails,
		}
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
