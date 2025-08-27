/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SetupCard from './components/setup-card';
import RSVPForm from './components/rsvp-form';
import './edit.pcss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   param0
 * @param {Object}   param0.attributes
 * @param {Function} param0.setAttributes
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const [ isSettingUp, setIsSettingUp ] = useState( false );
	const { rsvpId, limit } = attributes;
	
	// Check if RSVP already exists (has an ID)
	useEffect( () => {
		if ( rsvpId ) {
			setIsSettingUp( true );
		}
	}, [ rsvpId ] );

	const handleLimitChange = ( newLimit ) => {
		setAttributes( { limit: newLimit } );
	};

	const handleCancel = () => {
		// Only allow cancel if no RSVP ID exists yet
		if ( ! rsvpId ) {
			setIsSettingUp( false );
		}
	};

	const leftColumnContent = (
		<div className="tec-rsvp-block__setup-info">
			<h2 className="tec-rsvp-block__setup-title">
				{ __( 'Add an RSVP', 'event-tickets' ) }
			</h2>
			<p className="tec-rsvp-block__setup-description">
				{ __( 'Allow users to confirm their attendance.', 'event-tickets' ) }
			</p>
		</div>
	);

	const rightColumnContent = (
		<div className="tec-rsvp-block__setup-actions">
			<Button
				variant="primary"
				size="large"
				onClick={ () => setIsSettingUp( true ) }
			>
				{ __( 'Add RSVP', 'event-tickets' ) }
			</Button>
		</div>
	);

	return (
		<div { ...useBlockProps() }>
			{ ! isSettingUp && ! rsvpId ? (
				<SetupCard
					leftColumn={ leftColumnContent }
					rightColumn={ rightColumnContent }
					className="tec-rsvp-block__initial-setup"
				/>
			) : (
				<>
					<RSVPForm
						rsvpId={ rsvpId }
						limit={ limit }
						onLimitChange={ handleLimitChange }
						attributes={ attributes }
					/>
					{ ! rsvpId && (
						<div className="tec-rsvp-block__form-actions">
							<Button
								variant="primary"
								onClick={ () => {
									// Here we'll add the save functionality later
									console.log( 'Saving RSVP with limit:', limit );
								} }
							>
								{ __( 'Create RSVP', 'event-tickets' ) }
							</Button>
							<Button
								variant="secondary"
								onClick={ handleCancel }
							>
								{ __( 'Cancel', 'event-tickets' ) }
							</Button>
						</div>
					) }
				</>
			) }
		</div>
	);
}
