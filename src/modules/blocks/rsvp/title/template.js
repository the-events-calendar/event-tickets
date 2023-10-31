/**
 * External dependencies
 */
import React, { useEffect, useRef } from 'react';
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
import { LabeledItem } from '@moderntribe/common/elements';
import './styles.pcss';

const RSVPTitle = ( {
	isDisabled,
	onTempTitleChange,
	tempTitle,
} ) => {
	const titleId = uniqid();
	const inputRef = useRef( null );

	useEffect( () => {
		inputRef?.current?.focus();
	}, [ inputRef ] );

	return (
		<div className={ classNames(
			'tribe-editor__ticket__title',
			'tribe-editor__ticket__content-row',
			'tribe-editor__ticket__content-row--title',
		) }>
			<LabeledItem
				className="tribe-editor__ticket__title-label"
				forId={ titleId }
				isLabel={ true }
				label={ __( 'RSVP name', 'event-tickets' ) }
			/>

			<input
				className="tribe-editor__input tribe-editor__ticket__title-input"
				id={ titleId }
				disabled={ isDisabled }
				type="text"
				value={ tempTitle }
				onChange={ onTempTitleChange }
				ref={ inputRef }
			/>
		</div>
	);
};

RSVPTitle.propTypes = {
	isDisabled: PropTypes.bool,
	onTempTitleChange: PropTypes.func.isRequired,
	tempTitle: PropTypes.string,
};

export default RSVPTitle;
