/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.pcss';

const getFieldSummary = ( hasAttendeeInfoFields, fieldNames ) => {
	if ( fieldNames?.length ) {
		return fieldNames.join( ', ' );
	}

	if ( hasAttendeeInfoFields ) {
		return __( 'Fields configured', 'event-tickets' );
	}

	return __( 'No fields configured', 'event-tickets' );
};

const RSVPAttendeeInformationSection = ( {
	fieldNames,
	hasAttendeeInfoFields,
	onEdit,
	rsvpId,
	showEditAffordances,
} ) => {
	const summary = getFieldSummary( hasAttendeeInfoFields, fieldNames );
	const title = __( 'Attendee Information', 'event-tickets' );

	const handleEdit = () => {
		const handled = applyFilters( 'tec.tickets.blocks.rsvp.attendeeInformationEdit', null, { rsvpId } );

		if ( ! handled && onEdit ) {
			onEdit();
		}
	};

	return (
		<div className="tribe-editor__rsvp-attendee-information">
			{ showEditAffordances ? (
				<button
					className="tribe-editor__rsvp-inline-edit-button tribe-editor__rsvp-attendee-information__title-edit"
					onClick={ handleEdit }
					type="button"
				>
					<span className="tribe-editor__rsvp-attendee-information__title tribe-common-h6 tribe-common-h--alt">
						{ title }
					</span>
					<Dashicon className="tribe-editor__rsvp-inline-edit-button__icon" icon="edit" />
				</button>
			) : (
				<span className="tribe-editor__rsvp-attendee-information__title tribe-common-h6 tribe-common-h--alt">
					{ title }
				</span>
			) }
			<div className="tribe-editor__rsvp-attendee-information__fields tribe-common-b2">{ summary }</div>
		</div>
	);
};

RSVPAttendeeInformationSection.propTypes = {
	fieldNames: PropTypes.arrayOf( PropTypes.string ),
	hasAttendeeInfoFields: PropTypes.bool,
	onEdit: PropTypes.func,
	rsvpId: PropTypes.number,
	showEditAffordances: PropTypes.bool,
};

export default RSVPAttendeeInformationSection;
