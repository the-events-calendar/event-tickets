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
			singular={ __( '%d ticket available', 'event-tickets' ) }
			plural={ __( '%d tickets available', 'event-tickets' ) }
		/>
	);

	const Total = (
		<NumericLabel
			className={ classNames(
				'tribe-editor__tickets__availability-label',
				'tribe-editor__tickets__availability-label--total',
			) }
			count={ total }
			singular={ __( '%d total ticket', 'event-tickets' ) }
			plural={ __( '%d total tickets', 'event-tickets' ) }
		/>
	);

	return (
		<div className="tribe-editor__tickets__availability">
			<>
				{ Available }
				{ available
					? (
						<IconWithTooltip
							propertyName={ __(
								/* eslint-disable-next-line max-len */
								'Ticket availability is based on the lowest number of inventory, stock, and capacity.',
								'event-tickets',
							) }
							icon={ <span className="dashicons dashicons-info-outline" /> }
						/>
					)
					: null
				}
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
