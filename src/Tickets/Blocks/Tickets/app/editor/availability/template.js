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
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
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
					TICKET_LABELS.ticket.singularLowercase
				)
			}
			plural={
				'%d ' +
				// eslint-disable-next-line no-undef
				sprintf(
					/* Translators: %s - ticket plural label, lowercase */
					__('%s available', 'event-tickets'),
					TICKET_LABELS.ticket.pluralLowercase
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
			singular={ '%d ' + __( 'total capacity', 'event-tickets' ) } 
			plural={ '%d ' + __( 'total capacity', 'event-tickets' ) }
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
							TICKET_LABELS.ticket.singular
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
