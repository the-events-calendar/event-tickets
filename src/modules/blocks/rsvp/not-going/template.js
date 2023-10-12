/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import uniqid from 'uniqid';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabeledItem, Checkbox } from '@moderntribe/common/elements';
import './styles.pcss';

const RSVPNotGoingResponses = ( {
	isDisabled,
	onTempNotGoingResponsesChange,
	tempNotGoingResponses,
} ) => {
	const notGoingId = uniqid();

	return (
		<div className={ classNames(
			'tribe-editor__ticket__not-going-responses',
			'tribe-editor__ticket__content-row',
			'tribe-editor__ticket__content-row--not-going-responses',
		) }>
			<LabeledItem
				className="tribe-editor__ticket__not-going-responses-label"
				forId={ notGoingId }
				isLabel={ true }
				label={ __( 'Not going', 'event-tickets' ) }
			/>
			<div className="tribe-editor__rsvp-container-content__not-going-responses">
				<Checkbox
					checked={ tempNotGoingResponses }
					className="tribe-editor__rsvp-container-content__not-going-responses"
					disabled={ isDisabled }
					id={ notGoingId }
					label={ __( 'Enable "Not Going" responses', 'event-tickets' ) }
					onChange={ onTempNotGoingResponsesChange }
				/>
				<span className="tribe-editor__rsvp-container-content__not-going-responses-label-help">
					{ __( 'Receive notification of people who will not attend.', 'event-tickets' ) }
				</span>
			</div>
		</div>
	);
};

RSVPNotGoingResponses.propTypes = {
	isDisabled: PropTypes.bool,
	onTempNotGoingResponsesChange: PropTypes.func.isRequired,
	tempNotGoingResponses: PropTypes.string,
};

export default RSVPNotGoingResponses;
