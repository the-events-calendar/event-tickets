/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { Input, LabeledItem } from '@moderntribe/common/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './styles.pcss';

class Title extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onTempTitleChange: PropTypes.func.isRequired,
		tempTitle: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-title' );
	}

	render() {
		const {
			isDisabled,
			onTempTitleChange,
			tempTitle,
		} = this.props;

		return (
			<div className={ classNames(
				'tribe-editor__ticket__title',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--title',
			) }>
				<LabeledItem
					className="tribe-editor__ticket__title-label"
					forId={ this.id }
					isLabel={ true }
					// eslint-disable-next-line no-undef
					label={sprintf(
						/* Translators: %s - the singular label for a ticket. */
						__('%s name', 'event-tickets'),
						TICKET_LABELS.ticket.singular
					)}
				/>

				<Input
					className="tribe-editor__ticket__title-input"
					id={ this.id }
					type="text"
					value={ tempTitle }
					onChange={ onTempTitleChange }
					disabled={ isDisabled }
				/>
			</div>
		);
	}
}

export default Title;
