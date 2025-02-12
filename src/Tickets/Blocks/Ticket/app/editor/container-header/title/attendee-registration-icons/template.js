/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	ARF as ARFIcon,
	SaleWindow as SaleWindowIcon,
} from '@moderntribe/tickets/icons';
import { IconWithTooltip } from '@moderntribe/tickets/elements';
import './style.pcss';

const AttendeeRegistrationIcons = ( {
	attendeeInfoFieldsLabel,
	hasAttendeeInfoFields,
	isBlockSelected,
	fromDate,
	saleWindowLabel,
	toDate,
} ) => {
	if ( ! isBlockSelected ) {
		return null;
	}

	const saleWindowText = sprintf( __( '%s - %s', 'event-tickets' ), fromDate, toDate );

	return (
		<div className="tribe-editor__title__attendee-registration-icons">
			{
				hasAttendeeInfoFields
					? (
						<IconWithTooltip
							propertyName={ __( 'Attendee registration', 'event-tickets' ) }
							description={ attendeeInfoFieldsLabel }
							icon={ <ARFIcon /> }
						/>
					)
					: null
			}

			<IconWithTooltip
				propertyName={ saleWindowLabel }
				description={ saleWindowText }
				icon={ <SaleWindowIcon /> }
			/>
		</div>
	);
};

AttendeeRegistrationIcons.propTypes = {
	attendeeInfoFieldsLabel: PropTypes.string,
	clientId: PropTypes.string,
	fromDate: PropTypes.oneOfType([
		PropTypes.instanceOf(Date),
		PropTypes.string
	]).isRequired,
	hasAttendeeInfoFields: PropTypes.bool,
	isBlockSelected: PropTypes.bool,
	isSelected: PropTypes.bool,
	saleWindowLabel: PropTypes.string,
	toDate: PropTypes.oneOfType([
		PropTypes.instanceOf(Date),
		PropTypes.string
	]).isRequired,
};

export default AttendeeRegistrationIcons;
