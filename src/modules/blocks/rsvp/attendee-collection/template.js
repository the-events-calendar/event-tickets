/**
 * External dependencies
 */
import React, { PureComponent, Fragment } from 'react';
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
import RSVPIACSetting from './iac-setting/container';

class RSVPAttendeeCollection extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
	};

	constructor( props ) {
		super( props );
		this.accordionId = uniqid();
	}

	getHeader = () => (
		<Fragment>
			<Dashicon className="tribe-editor__rsvp__attendee-collection-header-icon" icon="arrow-down" />
			<span className="tribe-editor__rsvp__attendee-collection-header-text">
				{ __( 'Attendee Collection', 'event-tickets' ) }
			</span>
		</Fragment>
	);

	getContent = () => <RSVPIACSetting />;

	getRows = () => [
		{
			accordionId: this.accordionId,
			content: this.getContent(),
			contentClassName: 'tribe-editor__rsvp__attendee-collection-content',
			header: this.getHeader(),
			headerAttrs: { disabled: this.props.isDisabled },
			headerClassName: 'tribe-editor__rsvp__attendee-collection-header',
		},
	];

	render() {
		return <Accordion className="tribe-editor__rsvp__attendee-collection" rows={ this.getRows() } />;
	}
}

export default RSVPAttendeeCollection;
