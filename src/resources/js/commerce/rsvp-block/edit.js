/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Button, Placeholder, Spinner, Notice } from '@wordpress/components';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import SetupCard from './components/setup-card';
import RSVPForm from './components/rsvp-form';
import { RSVPInspectorControls } from './inspector-controls';
import { SettingsPanel, AdvancedPanel } from './inspector-controls/panels';
import { useCreateRSVP, useUpdateRSVP, useRSVP } from './api/hooks';
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
	const [ isActive, setIsActive ] = useState( false );
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId() );
	const { 
		rsvpId, 
		limit,
		openRsvpDate,
		openRsvpTime,
		closeRsvpDate,
		closeRsvpTime,
		attendeeInfoCollectionEnabled,
		showNotGoingOption,
		goingCount,
		notGoingCount
	} = attributes;

	// React Query hooks
	const { data: rsvpData, isLoading: isLoadingRsvp, error: loadError } = useRSVP( rsvpId );
	const createMutation = useCreateRSVP();
	const updateMutation = useUpdateRSVP();

	// Derive saving and error states from mutations
	const isSaving = createMutation.isPending || updateMutation.isPending;
	const saveError = createMutation.error || updateMutation.error;
	const saveSuccess = createMutation.isSuccess || updateMutation.isSuccess;
	
	// Check if RSVP already exists (has an ID)
	useEffect( () => {
		if ( rsvpId ) {
			setIsSettingUp( true );
			setIsActive( true );
		}
	}, [ rsvpId ] );

	// Set default dates when setting up
	useEffect( () => {
		if ( isSettingUp && ! openRsvpDate ) {
			const today = new Date();
			const tomorrow = new Date( today );
			tomorrow.setDate( tomorrow.getDate() + 1 );
			
			setAttributes( {
				openRsvpDate: today.toISOString().split( 'T' )[ 0 ],
				closeRsvpDate: tomorrow.toISOString().split( 'T' )[ 0 ]
			} );
		}
	}, [ isSettingUp, openRsvpDate, setAttributes ] );

	const handleLimitChange = useCallback( ( newLimit ) => {
		setAttributes( { limit: newLimit } );
	}, [ setAttributes ] );

	const handleAttributeChange = useCallback( ( updates ) => {
		setAttributes( updates );
	}, [ setAttributes ] );

	const handleCancel = useCallback( () => {
		// Only allow cancel if no RSVP ID exists yet
		if ( ! rsvpId ) {
			setIsSettingUp( false );
			// Reset attributes to defaults
			setAttributes( {
				limit: '',
				openRsvpDate: '',
				openRsvpTime: '00:00:00',
				closeRsvpDate: '',
				closeRsvpTime: '00:00:00'
			} );
		}
	}, [ rsvpId, setAttributes ] );

	const handleSave = useCallback( async () => {
		const data = {
			postId,
			limit,
			openRsvpDate,
			openRsvpTime,
			closeRsvpDate,
			closeRsvpTime,
			showNotGoingOption,
		};

		try {
			const result = await createMutation.mutateAsync( data );
			if ( result.ticket_id ) {
				setAttributes( { 
					rsvpId: String( result.ticket_id ),
				} );
				setIsActive( true );
			}
		} catch ( error ) {
			console.error( 'Error creating RSVP:', error );
		}
	}, [ postId, limit, openRsvpDate, openRsvpTime, closeRsvpDate, closeRsvpTime, showNotGoingOption, setAttributes, createMutation ] );

	const handleUpdate = useCallback( async () => {
		if ( ! rsvpId ) return;

		const data = {
			postId,
			rsvpId,
			limit,
			openRsvpDate,
			openRsvpTime,
			closeRsvpDate,
			closeRsvpTime,
			showNotGoingOption,
		};

		try {
			await updateMutation.mutateAsync( data );
		} catch ( error ) {
			console.error( 'Error updating RSVP:', error );
		}
	}, [ postId, rsvpId, limit, openRsvpDate, openRsvpTime, closeRsvpDate, closeRsvpTime, showNotGoingOption, updateMutation ] );

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

	const blockProps = useBlockProps( {
		className: 'tec-rsvp-block'
	} );

	// Inspector controls props
	const inspectorProps = useMemo( () => ( {
		attributes,
		setAttributes,
		isLoading: isLoadingRsvp,
		error: loadError,
		rsvpData,
		updateMutation,
	} ), [ attributes, setAttributes, isLoadingRsvp, loadError, rsvpData, updateMutation ] );

	return (
		<>
			{ /* Register the panel fills */ }
			<SettingsPanel />
			<AdvancedPanel />
			
			{ /* Render inspector controls in sidebar */ }
			{ ( isSettingUp || rsvpId ) && (
				<RSVPInspectorControls { ...inspectorProps } />
			) }
			
			<div { ...blockProps }>
				{ saveError && (
					<Notice 
						status="error" 
						isDismissible 
						onRemove={ () => createMutation.reset() }
					>
						{ saveError?.message || __( 'Failed to save RSVP. Please try again.', 'event-tickets' ) }
					</Notice>
				) }
				{ saveSuccess && (
					<Notice 
						status="success" 
						isDismissible
					>
						{ __( 'RSVP saved successfully!', 'event-tickets' ) }
					</Notice>
				) }
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
							setAttributes={ setAttributes }
							onAttributeChange={ handleAttributeChange }
							isActive={ isActive }
							onSave={ handleUpdate }
							isSaving={ isSaving }
						/>
						{ ! rsvpId && (
							<div className="tec-rsvp-block__form-actions">
								<Button
									variant="primary"
									onClick={ handleSave }
									disabled={ isSaving }
								>
									{ isSaving ? (
										<>
											<Spinner />
											{ __( 'Creating...', 'event-tickets' ) }
										</>
									) : (
										__( 'Create RSVP', 'event-tickets' )
									) }
								</Button>
								<Button
									variant="secondary"
									onClick={ handleCancel }
									disabled={ isSaving }
								>
									{ __( 'Cancel', 'event-tickets' ) }
								</Button>
							</div>
						) }
					</>
				) }
			</div>
		</>
	);
}
