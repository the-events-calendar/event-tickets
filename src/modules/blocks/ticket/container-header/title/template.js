/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import AutosizeInput from 'react-input-autosize';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pencil } from '@moderntribe/common/icons';
import './style.pcss';

const TicketContainerHeaderTitle = ( {
	isDisabled,
	isSelected,
	onTempTitleChange,
	tempTitle,
	title,
} ) => (
	<div className="tribe-editor__ticket__container-header-title">
		{
			isSelected
				? (
					<AutosizeInput
						className="tribe-editor__ticket__container-header-title-input"
						value={ tempTitle }
						placeholder={ __( 'Ticket Type', 'events-gutenberg' ) }
						onChange={ onTempTitleChange }
						disabled={ isDisabled }
					/>
				)
				: (
					<h3 className="tribe-editor__ticket__container-header-title-label">
						{ title }
					</h3>
				)
		}
		<Pencil />
	</div>
);

TicketContainerHeaderTitle.propTypes = {
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	onTempTitleChange: PropTypes.func,
	tempTitle: PropTypes.string,
	title: PropTypes.string,
};

export default TicketContainerHeaderTitle;
