/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { DateTimeRangePicker, LabelWithTooltip } from '../../../../../../../modules/elements';
import './style.pcss';

const TicketDuration = ( { hasDurationError, ...props } ) => (
	<div
		className={ classNames(
			'tribe-editor__ticket__duration',
			'tribe-editor__ticket__content-row',
			'tribe-editor__ticket__content-row--duration'
		) }
	>
		<LabelWithTooltip
			className="tribe-editor__ticket__duration-label-with-tooltip"
			label={ __( 'Sale Duration', 'event-tickets' ) }
			tooltipText={ __(
				'If you do not set a start sale date, tickets will be available immediately.',
				'event-tickets'
			) }
			tooltipLabel={ <Dashicon className="tribe-editor__ticket__tooltip-label" icon="info-outline" /> }
		/>
		{
			/**
			 * Filters what the Sale Duration section renders in place of its date and time range picker.
			 *
			 * @since TBD
			 *
			 * @param {Object} picker   The date and time range picker element.
			 * @param {string} clientId The client ID of the ticket block.
			 */
			applyFilters(
				'tec.tickets.blocks.Ticket.Duration.renderPicker',
				<DateTimeRangePicker className="tribe-editor__ticket__duration-picker" { ...props } />,
				props.clientId
			)
		}
		{ hasDurationError && (
			<span className="tribe-editor__ticket__duration-error">
				{ __(
					'There is an error with the selected sales duration. Please fix the issue before saving.', // eslint-disable-line max-len
					'event-tickets'
				) }
			</span>
		) }
	</div>
);

TicketDuration.propTypes = {
	clientId: PropTypes.string,
	fromDate: PropTypes.instanceOf( Date ),
	fromDateInput: PropTypes.string,
	fromDateDisabled: PropTypes.bool,
	fromTime: PropTypes.string,
	fromTimeDisabled: PropTypes.bool,
	hasDurationError: PropTypes.bool,
	onFromDateChange: PropTypes.func,
	onFromTimePickerBlur: PropTypes.func,
	onFromTimePickerChange: PropTypes.func,
	onFromTimePickerClick: PropTypes.func,
	onToDateChange: PropTypes.func,
	onToTimePickerBlur: PropTypes.func,
	onToTimePickerChange: PropTypes.func,
	onToTimePickerClick: PropTypes.func,
	toDate: PropTypes.instanceOf( Date ),
	toDateInput: PropTypes.string,
	toDateDisabled: PropTypes.bool,
	toTime: PropTypes.string,
	toTimeDisabled: PropTypes.bool,
};

export default TicketDuration;
