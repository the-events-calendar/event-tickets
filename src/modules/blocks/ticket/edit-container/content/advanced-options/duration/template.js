/**
 * External dependencies
 */
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DateTimeRangePicker, LabelWithTooltip } from '@moderntribe/tickets/elements';
import './style.pcss';

const TicketDurationPicker = ( { label, tooltip, isSameDay, ...remainingProps } ) => (
	<div className="tribe-editor__container-panel__row tribe-editor__container-panel__row--duration">
		<LabelWithTooltip
			className="tribe-editor__container-panel__label"
			label={ label }
			tooltipText={ tooltip }
			tooltipLabel={ <Dashicon icon="info-outline" /> }
		/>
		<div className="tribe-editor__container-panel__input-group">
			<DateTimeRangePicker { ...remainingProps } />
		</div>
	</div>
);

TicketDurationPicker.defaultProps = {
	className: 'tribe-editor__ticket-duration__duration-picker',
	label: __( 'Sale Duration', 'events-gutenberg' ),
	tooltip: __(
		'If you do not set a start sale date, tickets will be available immediately.',
		'events-gutenberg',
	),
};

export default TicketDurationPicker;
