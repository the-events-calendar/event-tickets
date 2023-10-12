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
import { Input, LabeledItem } from '@moderntribe/common/elements';
import './styles.pcss';

const RSVPDescription = ( {
	isDisabled,
	onTempDescriptionChange,
	tempDescription,
} ) => {
	const descriptionId = uniqid();

	return (
		<div className={ classNames(
			'tribe-editor__ticket__description',
			'tribe-editor__ticket__content-row',
			'tribe-editor__ticket__content-row--description',
		) }>
			<LabeledItem
				className="tribe-editor__ticket__description-label"
				forId={ descriptionId }
				isLabel={ true }
				label={ __( 'Description', 'event-tickets' ) }
			/>
			<Input
				className="tribe-editor__ticket__description-input"
				id={ descriptionId }
				disabled={ isDisabled }
				type="text"
				value={ tempDescription }
				onChange={ onTempDescriptionChange }
			/>
		</div>
	);
};

RSVPDescription.propTypes = {
	isDisabled: PropTypes.bool,
	onTempDescriptionChange: PropTypes.func.isRequired,
	tempDescription: PropTypes.string,
};

export default RSVPDescription;
