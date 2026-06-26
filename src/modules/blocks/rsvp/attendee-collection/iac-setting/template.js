/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import uniqid from 'uniqid';

class RSVPIACSetting extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onChange: PropTypes.func.isRequired,
		iac: PropTypes.string,
		iacOptions: PropTypes.arrayOf(
			PropTypes.shape( {
				label: PropTypes.string,
				value: PropTypes.string,
			} )
		),
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'rsvp-iac' );
	}

	render() {
		const { iac, iacOptions, isDisabled, onChange } = this.props;

		return (
			<div>
				<div className="tribe-editor__rsvp__content-row--iac-setting-description">
					{ __(
						'Select whether to collect individual respondent information. Enabling Individual Attendee Collection will allow respondents to enter a name and email for each RSVP.',
						'event-tickets'
					) }
				</div>
				<div
					className={ classNames(
						'tribe-editor__rsvp__iac-setting',
						'tribe-editor__rsvp__content-row',
						'tribe-editor__rsvp__content-row--iac-setting'
					) }
				>
					<RadioControl
						className="tribe-editor__rsvp__iac-setting-input"
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

export default RSVPIACSetting;
