/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

const NotSupportedMessage = ({ content, ctaLink }) => {
	return (
		<div className="tribe-editor__not-supported">
			{content && content}
			{ctaLink && ctaLink}
		</div>
	);
};

NotSupportedMessage.protoTypes = {
	content: PropTypes.string,
	ctaLink: PropTypes.node,
};

export default NotSupportedMessage;
