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
import { LabeledItem, NumberInput } from '@moderntribe/common/elements';
import './styles.pcss';

const RSVPCapacity = ( { isDisabled, onTempCapacityChange, tempCapacity } ) => {
	const capacityId = uniqid();

	return (
		<div
			className={ classNames(
				'tribe-editor__ticket__capacity',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--capacity'
			) }
		>
			<LabeledItem
				className="tribe-editor__ticket__capacity-label"
				forId={ capacityId }
				isLabel={ true }
				label={ __( 'RSVP Capacity', 'event-tickets' ) }
			/>
			<div className="tribe-editor__rsvp-container-content__capacity">
				<NumberInput
					className="tribe-editor__rsvp-container-content__capacity-input"
					disabled={ isDisabled }
					id={ capacityId }
					min={ 0 }
					onChange={ onTempCapacityChange }
					value={ tempCapacity }
				/>
				<span className="tribe-editor__rsvp-container-content__capacity-label-help">
					{ __( 'Leave blank if unlimited', 'event-tickets' ) }
				</span>
			</div>
		</div>
	);
};

RSVPCapacity.propTypes = {
	isDisabled: PropTypes.bool,
	onTempCapacityChange: PropTypes.func.isRequired,
	tempCapacity: PropTypes.string,
};

export default RSVPCapacity;
