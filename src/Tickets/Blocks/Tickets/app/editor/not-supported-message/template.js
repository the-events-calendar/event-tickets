/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';


const NotSupportedMessage = ({
	isBlockSelected,
}) => {
	return (
		isBlockSelected
		? (
			<div className="tribe-editor__not-supported">
				{
					__( 'Single tickets are not yet supported on recurring events. ' )
				}
				<a
					className="helper-link"
					href="https://evnt.is/1b7a"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Read about our plans for future features', 'event-tickets' ) }
				</a>
			</div>
		)
		: null
	);
};

NotSupportedMessage.propTypes = {
	isBlockSelected: PropTypes.bool,
};

export default NotSupportedMessage;
