/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import uniqid from 'uniqid';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Accordion } from '@moderntribe/common/elements';
import './style.pcss';
import IACSetting from './iac-setting/container';

class AttendeeCollection extends Component {
	static propTypes = {
		clientId: PropTypes.string.isRequired,
		isDisabled: PropTypes.bool,
		hasBeenCreated: PropTypes.bool,
	};

	constructor( props ) {
		super( props );
		this.accordionId = uniqid();
	}

	getHeader = () => (
		<Fragment>
			<Dashicon
				className="tribe-editor__ticket__attendee-collection-header-icon"
				icon="arrow-down"
			/>
			<span className="tribe-editor__ticket__attendee-collection-header-text">
				{ __( 'Attendee Collection', 'event-tickets' ) }
			</span>
		</Fragment>
	);

	getContent = () => (
		<Fragment>
			<IACSetting clientId={ this.props.clientId } />
		</Fragment>
	);

	getRows = () => ( [
		{
			accordionId: this.accordionId,
			content: this.getContent(),
			contentClassName: 'tribe-editor__ticket__attendee-collection-content',
			header: this.getHeader(),
			headerAttrs: { disabled: this.props.isDisabled },
			headerClassName: 'tribe-editor__ticket__attendee-collection-header',
		},
	] );

	render() {
		return (
			<Accordion
				className="tribe-editor__ticket__attendee-collection"
				rows={ this.getRows() }
			/>
		);
	}
}

export default AttendeeCollection;
