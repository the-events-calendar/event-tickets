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
import { NumericLabel } from '@moderntribe/tickets/elements';
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
			) }
			count={ available }
			singular={ __( '%d ticket available', 'events-gutenberg' ) }
			plural={ __( '%d tickets available', 'events-gutenberg' ) }
		/>
	);

	const Total = (
		<NumericLabel
			className={ classNames(
				'tribe-editor__tickets__availability-label',
				'tribe-editor__tickets__availability-label--total',
			) }
			count={ total }
			singular={ __( '%d total ticket', 'events-gutenberg' ) }
			plural={ __( '%d total tickets', 'events-gutenberg' ) }
		/>
	);

	return (
		<div className="tribe-editor__tickets__availability">
			{ Available }
			{ Total }
		</div>
	);
};

Availability.propTypes = {
	available: PropTypes.number,
	total: PropTypes.number,
}

export default Availability;
