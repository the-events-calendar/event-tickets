/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { NumericLabel } from '@moderntribe/tickets/elements';
import './style.pcss';

const Availability = ( { available, total, isDisabled, separator, ...labels } ) => {
	const Available = (
		<NumericLabel
			count={ available }
			singular={ labels.availableSingular }
			plural={ labels.availablePlural }
		/>
	);

	const Total = (
		<NumericLabel
			count={ total }
			singular={ labels.totalSingular }
			plural={ labels.totalPlural }
		/>
	);

	const Separator = ( ( !! available && !! total ) &&
		<span className="tribe-editor__availability--separator">{ separator }</span>
	);

	return (
		<div className={ classNames( 'tribe-editor__availability', { 'tribe-editor__availability--disabled': isDisabled } ) }>
			{ Available }
			{ Separator }
			{ Total }
		</div>
	);
};

Availability.propTypes = {
	available: PropTypes.number,
	total: PropTypes.number,
	availableSingular: PropTypes.string,
	availablePlural: PropTypes.string,
	totalSingular: PropTypes.string,
	totalPlural: PropTypes.string,
	separator: PropTypes.string,
	isDisabled: PropTypes.bool,
}

// todo: consider changing to __n for better translation compatibility
Availability.defaultProps = {
	isDisabled: false,
	available: 0,
	total: 0,
	availableSingular: __( '%d ticket available', 'events-gutenberg' ),
	availablePlural: __( '%d tickets available', 'events-gutenberg' ),
	totalSingular: __( '%d total ticket', 'events-gutenberg' ),
	totalPlural: __( '%d total tickets', 'events-gutenberg' ),
	separator: '|',
};

export default Availability;
