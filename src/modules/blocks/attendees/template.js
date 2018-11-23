/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import AutosizeInput from 'react-input-autosize';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { input } from '@moderntribe/common/utils';
import { AttendeesGravatar } from '@moderntribe/tickets/icons';
import './style.pcss';

/**
 * Module Code
 */

const placeholder = __( 'Who\'s Attending?', 'events-gutenberg' );
const subtitle    = __( 'How many people are attending this event', 'events-gutenberg' );

const renderLabelInput = ( { isSelected, isEmpty, title, setTitle } ) => {
	const containerClassNames = classNames( {
		'tribe-editor__event-attendees__title': true,
		'tribe-editor__event-attendees__title--selected': isSelected,
	} );

	const inputClassNames = classNames( {
		'tribe-editor__event-attendees__title-text': true,
		'tribe-editor__event-attendees__title-text--empty': isEmpty && isSelected,
	} );

	return (
		<div
			key="tribe-events-attendees-label"
			className={ containerClassNames }
		>
			<AutosizeInput
				id="tribe-events-attendees-link"
				className={ inputClassNames }
				value={ title }
				placeholder={ placeholder }
				onChange={ input.sendValue( setTitle ) }
			/>
		</div>
	);
};

const renderPlaceholder = () => {
	const classes = [
		'tribe-editor__event-attendees__title',
		'tribe-editor__event-attendees__title--placeholder',
	];

	return (
		<span className={ classNames( classes ) }>
			{ placeholder }
		</span>
	);
};

const RenderGravatars = () => (
	<div className="tribe-editor__event-attendees__gravatars">
		<AttendeesGravatar />
		<AttendeesGravatar />
		<AttendeesGravatar />
		<AttendeesGravatar />
		<AttendeesGravatar />
	</div>
);

const RenderSubtitle = () => (
	<div className="tribe-editor__event-attendees__subtitle">
		<p>{ subtitle }</p>
	</div>
);

const Attendees = ( props ) => {

	const { isSelected, title } = props;
	const blockTitle = ! ( isSelected || title )
		? renderPlaceholder()
		: [ renderLabelInput( props ) ];

	return (
		<div className="tribe-editor__block tribe-editor__event-attendees">
			{ blockTitle }
			{ <RenderSubtitle /> }
			{ <RenderGravatars /> }
		</div>
	);
};

Attendees.propTypes = {
	setTitle: PropTypes.func,
	title: PropTypes.string,
	isSelected: PropTypes.bool,
	isEmpty: PropTypes.bool,
};

export default Attendees;
