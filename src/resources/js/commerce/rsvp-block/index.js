/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { RSVPIcon } from './assets/icon';
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

// Create a QueryClient instance for React Query.
const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			staleTime: 5 * 60 * 1000, // 5 minutes.
			cacheTime: 10 * 60 * 1000, // 10 minutes.
			retry: 2,
			refetchOnWindowFocus: false,
		},
		mutations: {
			retry: 1,
		},
	},
} );

// Wrap Edit component with QueryClientProvider.
const EditWithProvider = ( props ) => (
	<QueryClientProvider client={ queryClient }>
		<Edit { ...props } />
	</QueryClientProvider>
);

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	...metadata,
	description: _x(
		'Find out who is planning to attend!',
		'The RSVP block description.',
		'event-tickets'
	),
	icon: RSVPIcon,
	edit: EditWithProvider,
	save: ( { attributes } ) => {
		// For dynamic blocks, we return null but WordPress will still save attributes.
		// in the block comment. This ensures attributes persist.
		return null;
	},
} );
