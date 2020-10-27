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
import { Dashicon, RadioControl } from '@wordpress/components';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { Input } from '@moderntribe/common/elements';
import { LabelWithTooltip } from '@moderntribe/tickets/elements';

class IACSetting extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onChange: PropTypes.func.isRequired,
		iac_setting: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-iac_setting' );
	}

	render() {
		const { iacSetting, isDisabled, onChange } = this.props;

		return (
			<div className={ classNames(
				'tribe-editor__ticket__iac_setting',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--iac_setting',
			) }>
				<LabelWithTooltip
					className="tribe-editor__ticket__iac_setting-label-with-tooltip"
					forId={ this.id }
					isLabel={ true }
					label={ __( 'Ticket SKU', 'event-tickets' ) }
					tooltipText={ __(
						'A unique identifying code for each ticket type you\'re selling',
						'event-tickets',
					) }
					tooltipLabel={ <Dashicon className="tribe-editor__ticket__tooltip-label" icon="info-outline" /> }
				/>
				<RadioControl
					className="tribe-editor__ticket__iac_setting-input"
					id={ this.id }
					type="text"
					value={ iacSetting }
					onChange={ onChange }
					disabled={ isDisabled }
					help={ __( 'Testing the help', 'event-tickets' ) }
					options={ [
						{
							label: __( 'No individual attendees', 'event-tickets' ),
							value: 'none',
						},
						{
							label: __( 'Allow individual attendees', 'event-tickets' ),
							value: 'allowed',
						},
						{
							label: __( 'Require individual attendees', 'event-tickets' ),
							value: 'required',
						},
					] }
				/>
			</div>
		);
	}
}

export default IACSetting;
