/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const RSVPRemoveRsvp = ( { isDisabled, isLoading, onRemove } ) => (
	<div className="tribe-editor__rsvp-remove">
		<Button disabled={ isDisabled || isLoading } onClick={ onRemove } type="button">
			{ __( 'Remove RSVP', 'event-tickets' ) }
		</Button>
	</div>
);

RSVPRemoveRsvp.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool.isRequired,
	onRemove: PropTypes.func.isRequired,
};

export default RSVPRemoveRsvp;
