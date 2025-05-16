/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '../../../elements';
import { LAYOUT } from '../../../elements/container-panel';
import RSVPContainerHeader from '../container-header/container';
import RSVPContainerContent from '../container-content/container';
import './style.pcss';

const RSVPContainer = ( { isDisabled, isSelected, clientId } ) => (
	<ContainerPanel
		className={ classNames( 'tribe-editor__rsvp-container', {
			'tribe-editor__rsvp-container--disabled': isDisabled,
		} ) }
		layout={ LAYOUT.rsvp }
		header={ <RSVPContainerHeader isSelected={ isSelected } /> }
		content={ <RSVPContainerContent clientId={ clientId } /> }
	/>
);

RSVPContainer.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
	isSelected: PropTypes.bool.isRequired,
	clientId: PropTypes.string.isRequired,
};

export default RSVPContainer;
