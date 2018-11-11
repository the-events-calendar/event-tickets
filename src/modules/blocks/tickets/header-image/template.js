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

const HeaderImage = ( props ) => <ImageUpload { ...props } />

HeaderImage.propTypes = {
	image: PropTypes.shape( {
		alt: PropTypes.string.isRequired,
		id: PropTypes.number.isRequired,
		src: PropTypes.string.isRequired,
	} ).isRequired,
	onRemove: PropTypes.func.isRequired,
	onSelect: PropTypes.func.isRequired,
};

HeaderImage.defaultProps = {
	title: __( 'Ticket Header Image', 'events-gutenberg' ),
	description: __(
		/* eslint-disable-next-line max-len */
		'Select an image from your Media Library to display on emailed tickets. For best results, use a .jpg, .png, or .gif at least 1160px wide.',
		'events-gutenberg'
	),
	buttonLabel: __( 'Upload Image', 'events-gutenberg' ),
}

export default HeaderImage;
