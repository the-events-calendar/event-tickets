/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Bulb as BulbIcon } from '../../icons';
import './style.pcss';

const Notice = ( { description } ) => {
	return (
		<div className="tribe-editor__notice">
			<BulbIcon />
			{ description }
		</div>
	);
};

Notice.propTypes = {
	description: PropTypes.node,
};

export default Notice;
