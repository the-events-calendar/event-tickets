/**
 * React Query hooks for RSVP API operations
 *
 * @since TBD
 */
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useSelect } from '@wordpress/data';
import { createRSVP, updateRSVP, deleteRSVP, fetchRSVP } from './endpoints';

/**
 * Hook for creating a new RSVP
 *
 * @since TBD
 *
 * @return {Object} Mutation object from React Query.
 */
export const useCreateRSVP = () => {
	const queryClient = useQueryClient();
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId() );

	return useMutation( {
		mutationFn: ( data ) => createRSVP( { ...data, postId } ),
		onSuccess: ( data ) => {
			// Invalidate queries to refresh data
			queryClient.invalidateQueries( { queryKey: [ 'rsvp', postId ] } );
			
			// Set the new RSVP data in cache
			if ( data.ticket_id ) {
				queryClient.setQueryData( [ 'rsvp', String( data.ticket_id ) ], data );
			}
		},
		onError: ( error ) => {
			console.error( 'Failed to create RSVP:', error );
		},
	} );
};

/**
 * Hook for updating an existing RSVP
 *
 * @since TBD
 *
 * @return {Object} Mutation object from React Query.
 */
export const useUpdateRSVP = () => {
	const queryClient = useQueryClient();
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId() );

	return useMutation( {
		mutationFn: ( data ) => updateRSVP( { ...data, postId } ),
		onSuccess: ( data, variables ) => {
			// Update the cached data
			if ( variables.rsvpId ) {
				queryClient.setQueryData( [ 'rsvp', variables.rsvpId ], data );
			}
			
			// Invalidate related queries
			queryClient.invalidateQueries( { queryKey: [ 'rsvp' ] } );
		},
		// Optimistic update for better UX
		onMutate: async ( newData ) => {
			// Cancel any outgoing refetches
			await queryClient.cancelQueries( { queryKey: [ 'rsvp', newData.rsvpId ] } );

			// Snapshot the previous value
			const previousData = queryClient.getQueryData( [ 'rsvp', newData.rsvpId ] );

			// Optimistically update to the new value
			if ( newData.rsvpId ) {
				queryClient.setQueryData( [ 'rsvp', newData.rsvpId ], ( old ) => ( {
					...old,
					...newData,
				} ) );
			}

			// Return a context with the previous data
			return { previousData };
		},
		// If the mutation fails, use the context to roll back
		onError: ( err, newData, context ) => {
			if ( context?.previousData && newData.rsvpId ) {
				queryClient.setQueryData( [ 'rsvp', newData.rsvpId ], context.previousData );
			}
		},
		// Always refetch after error or success
		onSettled: ( data, error, variables ) => {
			if ( variables?.rsvpId ) {
				queryClient.invalidateQueries( { queryKey: [ 'rsvp', variables.rsvpId ] } );
			}
		},
	} );
};

/**
 * Hook for deleting an RSVP
 *
 * @since TBD
 *
 * @return {Object} Mutation object from React Query.
 */
export const useDeleteRSVP = () => {
	const queryClient = useQueryClient();
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId() );

	return useMutation( {
		mutationFn: ( data ) => deleteRSVP( { ...data, postId } ),
		onSuccess: ( data, variables ) => {
			// Remove from cache
			if ( variables.rsvpId ) {
				queryClient.removeQueries( { queryKey: [ 'rsvp', variables.rsvpId ] } );
			}
			
			// Invalidate post RSVPs
			queryClient.invalidateQueries( { queryKey: [ 'rsvp', postId ] } );
		},
		onError: ( error ) => {
			console.error( 'Failed to delete RSVP:', error );
		},
	} );
};

/**
 * Hook for fetching RSVP data
 *
 * @since TBD
 *
 * @param {string} rsvpId The RSVP ID to fetch.
 *
 * @return {Object} Query object from React Query.
 */
export const useRSVP = ( rsvpId ) => {
	return useQuery( {
		queryKey: [ 'rsvp', rsvpId ],
		queryFn: () => fetchRSVP( rsvpId ),
		enabled: !! rsvpId,
		staleTime: 5 * 60 * 1000, // 5 minutes
		cacheTime: 10 * 60 * 1000, // 10 minutes
	} );
};

/**
 * Hook for fetching all RSVPs for a post
 *
 * @since TBD
 *
 * @return {Object} Query object from React Query.
 */
export const usePostRSVPs = () => {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId() );

	return useQuery( {
		queryKey: [ 'rsvp', 'post', postId ],
		queryFn: async () => {
			if ( ! postId ) return [];
			
			try {
				// Fetch all tickets for this post via REST API
				const response = await fetch( `/wp-json/tribe/tickets/v1/tickets?include_post=${postId}` );
				
				if ( ! response.ok ) {
					console.error( 'Failed to fetch tickets:', response.statusText );
					return [];
				}
				
				const data = await response.json();
				const tickets = data?.tickets || [];
				
				// Filter for tc_rsvp type tickets
				const rsvps = tickets.filter( ticket => 
					ticket.type === 'tc_rsvp' || 
					ticket.provider_class === 'TEC\\Tickets\\Commerce\\Module'
				);
				
				return rsvps;
			} catch ( error ) {
				console.error( 'Error fetching RSVPs:', error );
				return [];
			}
		},
		enabled: !! postId,
		staleTime: 5 * 60 * 1000, // 5 minutes
		cacheTime: 10 * 60 * 1000, // 10 minutes
	} );
};