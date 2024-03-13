/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import NotSupportedMessage from './template';
import { withStore } from '@moderntribe/common/hoc';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

const getCurrentPostStatus = () => {
	const { status = 'auto-draft' } = select('core/editor').getCurrentPost();
	return status;
};

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		// eslint-disable-next-line no-undef
		content: sprintf(
			/* Translators: %s - the plural, lowercase label for a ticket. */
			__(
				'Standard %s are not yet supported on recurring events. ',
				'event-tickets'
			),
			TICKET_LABELS.ticket.pluralLowercase
		),
		ctaLink: (
			<a
				className="helper-link"
				href="https://evnt.is/1b7a"
				target="_blank"
				rel="noopener noreferrer"
			>
				{__(
					'Read about our plans for future features',
					'event-tickets'
				)}
			</a>
		),
		// While not directly used in the render, this prop will trigger a refresh on change of the post status.
		postStatus: getCurrentPostStatus(),
	};

	/**
	 * Filters the properties mapped from the state for the NotSupportedMessage component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object}      mappedProps         The mapped props.
	 * @param {string|null} mappedProps.content The message content.
	 * @param {Node|null}   mappedProps.ctaLink The call-to-action link.
	 * @param {Object}      context.state       The state of the block.
	 * @param {Object}      context.ownProps    The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.NotSupportedMessage.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

export default compose(
	withStore(),
	connect(mapStateToProps)
)(NotSupportedMessage);
