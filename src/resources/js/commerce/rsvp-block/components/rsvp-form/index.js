/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { TextControl, BaseControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './styles.pcss';

const RSVPForm = ( { rsvpId, limit, onLimitChange, attributes } ) => {
	return (
		<div className="tec-rsvp-block__form-wrapper">
			<div className="tec-rsvp-block__form-header">
				<h3>{ __( 'RSVP Settings', 'event-tickets' ) }</h3>
			</div>
			
			<div className="tec-rsvp-block__form-fields">
				{/* Hidden RSVP ID field */}
				<input
					type="hidden"
					name="rsvp_id"
					value={ rsvpId || '' }
				/>
				
				{/* Limit field */}
				<BaseControl
					id="tec-rsvp-limit"
					label={ __( 'Limit', 'event-tickets' ) }
					help={ __( 'Leave blank for unlimited', 'event-tickets' ) }
					className="tec-rsvp-block__field"
				>
					<TextControl
						type="number"
						value={ limit }
						onChange={ onLimitChange }
						placeholder={ __( 'Unlimited', 'event-tickets' ) }
						min="0"
						step="1"
					/>
				</BaseControl>
			</div>
		</div>
	);
};

RSVPForm.propTypes = {
	rsvpId: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.number,
	] ),
	limit: PropTypes.string,
	onLimitChange: PropTypes.func.isRequired,
	attributes: PropTypes.object,
};

RSVPForm.defaultProps = {
	rsvpId: null,
	limit: '',
	attributes: {},
};

export default RSVPForm;