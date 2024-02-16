/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ImageUpload } from '@moderntribe/common/elements';
import './style.pcss';

const HeaderImage = ( {
	image,
	isSettingsLoading,
	onRemove,
	onSelect,
} ) => {
	const description =
		!image?.src &&
		// eslint-disable-next-line no-undef
		sprintf(
			/* Translators: %s - Ticket plural label. */
			__(
				/* eslint-disable-next-line max-len */
				'Select an image from your Media Library to display on emailed %s and RSVPs. For best results, use a .jpg, .png, or .gif at least 1160px wide.',
				'event-tickets'
			),
			tribe_editor_config.tickets.ticketLabels.ticket.plural_lowercase // eslint-disable-line camelcase, no-undef
		);

	const imageUploadProps = {
		// eslint-disable-next-line no-undef
		title: sprintf(
			/* Translators: %s - Ticket singular label. */
			__('%s Header Image', 'event-tickets'),
			tribe_editor_config.tickets.ticketLabels.ticket.singular // eslint-disable-line camelcase, no-undef
		),
		description,
		className: 'tribe-editor__rsvp__image-upload',
		buttonDisabled: isSettingsLoading,
		buttonLabel: __( 'Set Header Image', 'event-tickets' ),
		image,
		onRemove,
		onSelect,
		removeButtonDisabled: isSettingsLoading,
	};

	return <ImageUpload { ...imageUploadProps } />;
};

HeaderImage.propTypes = {
	image: PropTypes.shape( {
		alt: PropTypes.string.isRequired,
		id: PropTypes.number.isRequired,
		src: PropTypes.string.isRequired,
	} ).isRequired,
	isSettingsLoading: PropTypes.bool.isRequired,
	onRemove: PropTypes.func.isRequired,
	onSelect: PropTypes.func.isRequired,
};

export default HeaderImage;
