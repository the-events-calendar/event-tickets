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
import {RadioControl} from '@wordpress/components';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */

class IACSetting extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onChange: PropTypes.func.isRequired,
		iac: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-iac' );
	}

	render() {
		const { iac, isDisabled, onChange } = this.props;

		const options = window.tribe_tickets_plus_iac_vars.iac_options;

		let iacSetting = iac;

		// Set the default for new tickets.
		if ( '' === iacSetting ) {
			iacSetting = window.tribe_tickets_plus_iac_vars.iac_default;
		}

		return (
			<div>
				<div className="tribe-editor__ticket__content-row--iac-setting-description">
					{ __( 'Select the default way to sell your tickets. Individual Attendee Collection gives you the control to allow purchasers to enter a name and email for each ticket, which you can also require as unique.', 'event-tickets' ) }
				</div>
				<div className={ classNames(
					'tribe-editor__ticket__iac-setting',
					'tribe-editor__ticket__content-row',
					'tribe-editor__ticket__content-row--iac-setting',
				) }>
					<RadioControl
						className="tribe-editor__ticket__iac-setting-input"
						id={ this.id }
						type="text"
						selected={ iacSetting }
						onChange={ onChange }
						disabled={ isDisabled }
						options={ options }
					/>
				</div>
			</div>
		);
	}
}

export default IACSetting;
