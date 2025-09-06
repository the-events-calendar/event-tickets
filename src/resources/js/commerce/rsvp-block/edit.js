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
import ActiveRSVP from './components/active-rsvp';
import { RSVPInspectorControls } from './inspector-controls';
import { SettingsPanel, AdvancedPanel } from './inspector-controls/panels';
import { useCreateRSVP, useUpdateRSVP, useRSVP, usePostRSVPs, useDeleteRSVP } from './api/hooks';
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
	const { data: rsvpData, isLoading: isLoadingRsvp, error: loadError, refetch: refetchRsvp } = useRSVP( rsvpId );
	const { data: existingRSVPs = [], isLoading: isLoadingExisting, refetch: refetchExisting } = usePostRSVPs();
	const createMutation = useCreateRSVP();
	const updateMutation = useUpdateRSVP();
	const deleteMutation = useDeleteRSVP();

	// Derive saving and error states from mutations
	const isSaving = createMutation.isPending || updateMutation.isPending;
	const saveError = createMutation.error || updateMutation.error;
	const saveSuccess = createMutation.isSuccess || updateMutation.isSuccess;
	
	// Check if RSVP already exists (has an ID or exists on the post)
	useEffect( () => {
		if ( rsvpId ) {
			setIsSettingUp( true );
			setIsActive( true );
		} else if ( existingRSVPs && existingRSVPs.length > 0 ) {
			// If there's an existing RSVP but no rsvpId in attributes, set it
			const existingRsvp = existingRSVPs[0];
			if ( existingRsvp?.id ) {
				setAttributes( { 
					rsvpId: String( existingRsvp.id ),
					limit: String( existingRsvp.capacity || '' ),
					openRsvpDate: existingRsvp.start_date || '',
					openRsvpTime: existingRsvp.start_time || '00:00:00',
					closeRsvpDate: existingRsvp.end_date || '',
					closeRsvpTime: existingRsvp.end_time || '00:00:00',
					showNotGoingOption: existingRsvp.show_not_going || false,
					goingCount: existingRsvp.going_count || 0,
					notGoingCount: existingRsvp.not_going_count || 0,
				} );
				setIsSettingUp( true );
				setIsActive( true );
			}
		}
	}, [ rsvpId, existingRSVPs, setAttributes ] );

	// Sync fetched RSVP data back to block attributes
	useEffect( () => {
		if ( rsvpData && rsvpId ) {
			// Sync all the RSVP data from API to block attributes
			const updates = {};
			
			// Only update if values are different to avoid infinite loops
			if ( rsvpData.capacity !== undefined && String( rsvpData.capacity || '' ) !== limit ) {
				updates.limit = String( rsvpData.capacity || '' );
			}
			
			if ( rsvpData.start_date && rsvpData.start_date !== openRsvpDate ) {
				updates.openRsvpDate = rsvpData.start_date;
			}
			
			if ( rsvpData.start_time && rsvpData.start_time !== openRsvpTime ) {
				updates.openRsvpTime = rsvpData.start_time;
			}
			
			if ( rsvpData.end_date && rsvpData.end_date !== closeRsvpDate ) {
				updates.closeRsvpDate = rsvpData.end_date;
			}
			
			if ( rsvpData.end_time && rsvpData.end_time !== closeRsvpTime ) {
				updates.closeRsvpTime = rsvpData.end_time;
			}
			
			if ( rsvpData.show_not_going !== undefined && rsvpData.show_not_going !== showNotGoingOption ) {
				updates.showNotGoingOption = rsvpData.show_not_going;
			}
			
			if ( rsvpData.going_count !== undefined && rsvpData.going_count !== goingCount ) {
				updates.goingCount = rsvpData.going_count || 0;
			}
			
			if ( rsvpData.not_going_count !== undefined && rsvpData.not_going_count !== notGoingCount ) {
				updates.notGoingCount = rsvpData.not_going_count || 0;
			}
			
			// Only call setAttributes if there are updates
			if ( Object.keys( updates ).length > 0 ) {
				setAttributes( updates );
			}
		}
	}, [ rsvpData, rsvpId, setAttributes, limit, openRsvpDate, openRsvpTime, closeRsvpDate, closeRsvpTime, showNotGoingOption, goingCount, notGoingCount ] );

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
		// Check if there's already an RSVP
		if ( existingRSVPs && existingRSVPs.length > 0 ) {
			// Already has an RSVP, should update instead
			const existingRsvp = existingRSVPs[0];
			if ( existingRsvp?.id ) {
				setAttributes( { rsvpId: String( existingRsvp.id ) } );
				setIsActive( true );
				return;
			}
		}
		
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
				// Save all attributes to the block
				setAttributes( { 
					rsvpId: String( result.ticket_id ),
					limit: limit || '',
					openRsvpDate: openRsvpDate || '',
					openRsvpTime: openRsvpTime || '00:00:00',
					closeRsvpDate: closeRsvpDate || '',
					closeRsvpTime: closeRsvpTime || '00:00:00',
					showNotGoingOption: showNotGoingOption || false,
				} );
				setIsActive( true );
				// Refetch existing RSVPs to update the list
				refetchExisting();
			}
		} catch ( error ) {
			console.error( 'Error creating RSVP:', error );
		}
	}, [ postId, limit, openRsvpDate, openRsvpTime, closeRsvpDate, closeRsvpTime, showNotGoingOption, setAttributes, createMutation, existingRSVPs, refetchExisting ] );

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
			// Refetch RSVP data after update
			refetchRsvp();
			refetchExisting();
		} catch ( error ) {
			console.error( 'Error updating RSVP:', error );
		}
	}, [ postId, rsvpId, limit, openRsvpDate, openRsvpTime, closeRsvpDate, closeRsvpTime, showNotGoingOption, updateMutation, refetchRsvp, refetchExisting ] );

	const handleDelete = useCallback( async () => {
		if ( ! rsvpId ) return;

		try {
			await deleteMutation.mutateAsync( { rsvpId, postId } );
			// Reset attributes after deletion
			setAttributes( {
				rsvpId: '',
				limit: '',
				openRsvpDate: '',
				openRsvpTime: '00:00:00',
				closeRsvpDate: '',
				closeRsvpTime: '00:00:00',
				goingCount: 0,
				notGoingCount: 0
			} );
			setIsActive( false );
			setIsSettingUp( false );
			refetchExisting();
		} catch ( error ) {
			console.error( 'Error deleting RSVP:', error );
		}
	}, [ rsvpId, postId, deleteMutation, setAttributes, refetchExisting ] );

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
				) : rsvpId && isActive ? (
					<ActiveRSVP
						rsvpId={ rsvpId }
						attributes={ attributes }
						setAttributes={ setAttributes }
						onUpdate={ handleUpdate }
						onDelete={ handleDelete }
						isSaving={ isSaving }
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
