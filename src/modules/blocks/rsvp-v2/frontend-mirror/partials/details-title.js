/**
 * Mirrors `src/views/v2/commerce/rsvp/details/title.php`.
 */
import React from 'react';
import PropTypes from 'prop-types';

const RSVPDetailsTitle = ( { title } ) => (
	<h3 className="tribe-tickets__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">{ title }</h3>
);

RSVPDetailsTitle.propTypes = {
	title: PropTypes.string.isRequired,
};

export default RSVPDetailsTitle;
