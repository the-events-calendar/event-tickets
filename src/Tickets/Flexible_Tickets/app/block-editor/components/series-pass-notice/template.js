/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * Wordpress dependencies
 */
import { _x, sprintf } from '@wordpress/i18n';
import { renderToString } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Notice } from '@moderntribe/tickets/elements';

const SeriesPassNotice = ({ seriesPassLink, seriesName }) => {
	return (
		<div>
			<Notice
				description={
					<div
						dangerouslySetInnerHTML={{
							__html: sprintf(
								_x(
									'Create and manage Series Passes from the %s Series admin.',
									'The message displayed to a user editing an Event part of a Series with ' +
										'Series Passes to let them know where to manage Series Passes.',
									'event-tickets'
								),
								renderToString(
									<a
										className="helper-link"
										href={seriesPassLink}
										target="_blank"
										rel="noopener noreferrer"
									>
										{seriesName}
									</a>
								)
							),
						}}
					></div>
				}
			/>
		</div>
	);
};

SeriesPassNotice.propTypes = {
	seriesPassLink: PropTypes.string,
	seriesName: PropTypes.string,
};

export default SeriesPassNotice;
