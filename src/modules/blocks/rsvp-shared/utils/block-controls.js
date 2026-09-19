/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Filter name for RSVP block sidebar controls.
 *
 * @type {string}
 */
export const RSVP_CONTROLS_FILTER = 'tec.tickets.blocks.RSVP.Controls';

/**
 * Get the block controls for the RSVP block.
 *
 * @since 5.20.0
 * @return {Array} The block controls.
 */
export function getRSVPBlockControls() {
	const controls = [];

	/**
	 * Filters the RSVP block controls.
	 *
	 * @since 5.20.0
	 * @param {Array} controls The existing controls.
	 */
	return applyFilters( RSVP_CONTROLS_FILTER, controls );
}

/**
 * The RSVP block controls.
 *
 * @since 5.20.0
 * @return {Node} The RSVP block controls.
 */
export const RSVPControls = () => {
	const controls = getRSVPBlockControls();

	if ( ! controls.length ) {
		return null;
	}

	return <InspectorControls key="inspector">{ controls }</InspectorControls>;
};
