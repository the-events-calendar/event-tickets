/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { IconWithTooltip, NumericLabel } from '@moderntribe/tickets/elements';
import './style.pcss';

/**
 * @todo: consider changing to _n for better translation compatibility
 */

const Availability = ( { available, total } ) => {
	const Available = (
		<NumericLabel
			className={ classNames(
				'tribe-editor__tickets__availability-label',
				'tribe-editor__tickets__availability-label--available',
				'tribe-tooltip',
			) }
			count={ available }
			singular={
				'%d ' +
				// eslint-disable-next-line no-undef
				sprintf(
					/* Translators: %s - ticket singular label, lowercase */
					__('%s available', 'event-tickets'),
					// eslint-disable-next-line no-undef, camelcase
					tribe_editor_config.tickets.ticketLabels.ticket
						.singular_lowercase
				)
			}
			plural={
				'%d ' +
				// eslint-disable-next-line no-undef
				sprintf(
					/* Translators: %s - ticket plural label, lowercase */
					__('%s available', 'event-tickets'),
					// eslint-disable-next-line no-undef, camelcase
					tribe_editor_config.tickets.ticketLabels.ticket
						.plural_lowercase
				)
			}
		/>
	);

	const Total = (
		<NumericLabel
			className={ classNames(
				'tribe-editor__tickets__availability-label',
				'tribe-editor__tickets__availability-label--total',
			) }
			count={ total }
			singular={
				'%d ' +
				// eslint-disable-next-line no-undef
				sprintf(
					/* Translators: %s - ticket singular label, lowercase */
					__('total %s', 'event-tickets'),
					// eslint-disable-next-line no-undef, camelcase
					tribe_editor_config.tickets.ticketLabels.ticket
						.singular_lowercase
				)
			}
			plural={
				'%d ' +
				// eslint-disable-next-line no-undef
				sprintf(
					/* Translators: %s - ticket plural label, lowercase */
					__('total %s', 'event-tickets'),
					// eslint-disable-next-line no-undef, camelcase
					tribe_editor_config.tickets.ticketLabels.ticket
						.plural_lowercase
				)
			}
		/>
	);

	return (
		<div className="tribe-editor__tickets__availability">
			<>
				{ Available }
				{available ? (
					<IconWithTooltip
						// eslint-disable-next-line no-undef
						propertyName={sprintf(
							/* Translators: %s - the singular label for a ticket. */
							__(
								'%s availability is based on the lowest number of inventory, stock, and capacity.',
								'event-tickets'
							),
							// eslint-disable-next-line camelcase, no-undef
							tribe_editor_config.tickets.ticketLabels.ticket
								.singular
						)}
						icon={
							<span className="dashicons dashicons-info-outline" />
						}
					/>
				) : null}
			</>
			{ Total }
		</div>
	);
};

Availability.propTypes = {
	available: PropTypes.number,
	total: PropTypes.number,
};

export default Availability;
