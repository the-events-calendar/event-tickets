/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Notice } from '@moderntribe/tickets/elements';

const PromptInfo = ( { seriesPassLink, seriesName } ) => (
	<div>
		<Notice
			description={
				<div>
					{ __( 'Create and manage Series Passes from the ', 'event-tickets' ) }
					<a className="helper-link" href={ seriesPassLink } target="_blank" rel="noopener noreferrer">
						{
							// translators: %s is the series name.
							sprintf( _x( '%s', 'event-tickets' ), seriesName )
						}
					</a>
					{ __( ' Series admin.', 'event-tickets' ) }
				</div>
			}
		/>
	</div>
);

PromptInfo.propTypes = {
	seriesPassLink: PropTypes.string,
	seriesName: PropTypes.string,
};

export default PromptInfo;