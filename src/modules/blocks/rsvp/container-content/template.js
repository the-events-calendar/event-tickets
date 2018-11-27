/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';
import uniqid from 'uniqid';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPAdvancedOptions from '@moderntribe/tickets/blocks/rsvp/advanced-options/container';
import { Checkbox } from '@moderntribe/common/elements';
import './style.pcss';

const RSVPContainerContentLabels = () => (
	<div className="tribe-editor__rsvp-container-content__labels">
		<span className="tribe-editor__rsvp-container-content__capacity-label">
			{ __( 'RSVP Capacity', 'events-gutenberg' ) }
		</span>
		<span className="tribe-editor__rsvp-container-content__capacity-label-help">
			{ __( 'Leave blank if unlimited', 'events-gutenberg' ) }
		</span>
	</div>
);

const RSVPContainerContentOptions = ( {
	capacityId,
	isDisabled,
	notGoingId,
	onTempCapacityChange,
	onTempNotGoingResponsesChange,
	tempCapacity,
	tempNotGoingResponses,
} ) => (
	<div className="tribe-editor__rsvp-container-content__options">
		<input
			className="tribe-editor__rsvp-container-content__capacity-input"
			disabled={ isDisabled }
			id={ capacityId }
			min="0"
			onChange={ onTempCapacityChange }
			type="number"
			value={ tempCapacity }
		/>
		<Checkbox
			checked={ tempNotGoingResponses }
			className="tribe-editor__rsvp-container-content__not-going-responses"
			disabled={ isDisabled }
			id={ notGoingId }
			label={ __( 'Enable "Not Going responses"', 'events-gutenberg' ) }
			onChange={ onTempNotGoingResponsesChange }
		/>
	</div>
);

RSVPContainerContentOptions.propTypes = {
	capacity: PropTypes.string,
	capacityId: PropTypes.string.isRequired,
	notGoingId: PropTypes.string.isRequired,
	notGoingResponses: PropTypes.bool,
};

class RSVPContainerContent extends PureComponent {
	static propTypes = {
		capacity: PropTypes.string,
		notGoingResponses: PropTypes.bool,
	}

	constructor( props ) {
		super( props );
		this.capacityId = uniqid();
		this.notGoingId = uniqid();
	}

	render() {
		const {
			isDisabled,
			onTempCapacityChange,
			onTempNotGoingResponsesChange,
			tempCapacity,
			tempNotGoingResponses,
		} = this.props;
		const optionsProps = {
			capacityId: this.capacityId,
			isDisabled,
			notGoingId: this.notGoingId,
			onTempCapacityChange,
			onTempNotGoingResponsesChange,
			tempCapacity,
			tempNotGoingResponses,
		}

		return (
			<Fragment>
				<RSVPContainerContentLabels />
				<RSVPContainerContentOptions { ...optionsProps } />
				<RSVPAdvancedOptions />
			</Fragment>
		);
	}
}

export default RSVPContainerContent;
