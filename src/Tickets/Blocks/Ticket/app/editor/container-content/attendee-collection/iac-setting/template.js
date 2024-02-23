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
import { RadioControl } from '@wordpress/components';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

class IACSetting extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onChange: PropTypes.func.isRequired,
		iac: PropTypes.string,
		iacOptions: PropTypes.arrayOf( PropTypes.shape( {
			label: PropTypes.string,
			value: PropTypes.string,
		} ) ),
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-iac' );
	}

	render() {
		const { iac, iacOptions, isDisabled, onChange } = this.props;

		return (
			<div>
				<div className="tribe-editor__ticket__content-row--iac-setting-description">
					{
						// eslint-disable-next-line no-undef
						sprintf(
							/* Translators: %1$s - the plural, lowercase label for a ticket;  %2$s - the singular, lowercase label for a ticket. */
							__(
								'Select the default way to sell %1$s. Enabling Individual Attendee Collection will allow purchasers to enter a name and email for each %2$s.', // eslint-disable-line max-len
								'event-tickets'
							),
							TICKET_LABELS.ticket.pluralLowercase,
							TICKET_LABELS.ticket.singularLowercase
						)
					}
				</div>
				<div className={ classNames(
					'tribe-editor__ticket__iac-setting',
					'tribe-editor__ticket__content-row',
					'tribe-editor__ticket__content-row--iac-setting',
				) }>
					<RadioControl
						className="tribe-editor__ticket__iac-setting-input"
						id={ this.id }
						type="radio"
						selected={ iac }
						onChange={ onChange }
						disabled={ isDisabled }
						options={ iacOptions }
					/>
				</div>
			</div>
		);
	}
}

export default IACSetting;
