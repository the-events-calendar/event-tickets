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
import './styles.pcss';

class Description extends PureComponent {
	static defaultProps = {
		isDisabled: false,
		onTempDescriptionChange: () => {},
		tempDescription: '',
	};

	static propTypes = {
		isDisabled: PropTypes.bool,
		onTempDescriptionChange: PropTypes.func.isRequired,
		tempDescription: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-description' );
	}

	render() {
		const {
			isDisabled,
			onTempDescriptionChange,
			tempDescription,
		} = this.props;

		return (
			<div className={ classNames(
				'tribe-editor__ticket__description',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--description',
			) }>
				<LabeledItem
					className="tribe-editor__ticket__description-label"
					forId={ this.id }
					isLabel={ true }
					label={ __( 'Description', 'event-tickets' ) }
				/>

				<Input
					className="tribe-editor__ticket__description-input"
					id={ this.id }
					type="text"
					value={ tempDescription }
					onChange={ onTempDescriptionChange }
					disabled={ isDisabled }
				/>
			</div>
		);
	}
}

export default Description;
