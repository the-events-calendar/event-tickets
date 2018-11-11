/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '@moderntribe/tickets/elements';
import { LAYOUT } from '@moderntribe/tickets/elements/container-panel/element';
import RSVPContainerHeader from '@moderntribe/tickets/blocks/rsvp/container-header/container';
import RSVPContainerContent from '@moderntribe/tickets/blocks/rsvp/container-content/container';
import { RSVPActive, RSVPInactive } from '@moderntribe/tickets/icons';
import './style.pcss';

const RSVPContainerIcon = ( { isDisabled } ) => (
	<Fragment>
		{
			isDisabled
				? <RSVPInactive />
				: <RSVPActive />
		}
		<span className="tribe-editor__rsvp-container__icon-label">
			{ __( 'RSVP', 'events-gutenberg' ) }
		</span>
	</Fragment>
);

RSVPContainerIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const RSVPContainer = ( { isDisabled, isSelected } ) => (
	<ContainerPanel
		className={ classNames(
			'tribe-editor__rsvp-container',
			{ 'tribe-editor__rsvp-container--disabled': isDisabled },
		) }
		layout={ LAYOUT.rsvp }
		icon={ <RSVPContainerIcon isDisabled={ isDisabled } /> }
		header={ <RSVPContainerHeader isSelected={ isSelected } /> }
		content={ <RSVPContainerContent /> }
	/>
);

RSVPContainer.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
	isSelected: PropTypes.bool.isRequired,
};

export default RSVPContainer;
