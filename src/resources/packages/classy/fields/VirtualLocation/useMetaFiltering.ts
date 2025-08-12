import { useEffect } from '@wordpress/element';
import { addFilter, hasFilter, removeFilter } from '@wordpress/hooks';

/**
 * Custom hook that manages WordPress filters for virtual location meta updates and deletions.
 * Dynamically adds/removes filters based on dependencies to ensure meta data is properly
 * filtered when saving or unsetting virtual location settings.
 *
 * @since TBD
 *
 * @param {Function} onMetaUpdate - Callback function to filter meta data on update. Receives and returns meta object.
 * @param {Function} onMetaDelete - Callback function to filter meta data on deletion. Receives and returns meta object.
 * @param {Array} dependencies - React dependency array that triggers filter re-registration when values change.
 *
 * @return void
 */
export default function useMetaFiltering(
	onMetaUpdate: ( meta: Object ) => Object,
	onMetaDelete: ( meta: Object ) => Object,
	dependencies: any[]
): void {
	/**
	 * When one, or both, the settings change, this effect will apply filtering the
	 * saved meta for the post using updated values.
	 */
	useEffect( () => {
		// Remove the filter if it exists.
		if ( hasFilter( 'tec.classy.events-pro.virtual-location.meta.update', 'tec.classy.event-tickets' ) ) {
			removeFilter( 'tec.classy.events-pro.virtual-location.meta.update', 'tec.classy.event-tickets' );
		}

		// Add or re-add the filter.
		addFilter( 'tec.classy.events-pro.virtual-location.meta.update', 'tec.classy.event-tickets', onMetaUpdate );

		// This filter does not depend on the updated values and will be added only once.
		if ( ! hasFilter( 'tec.classy.events-pro.virtual-location.meta.unset', 'tec.classy.event-tickets' ) ) {
			addFilter( 'tec.classy.events-pro.virtual-location.meta.unset', 'tec.classy.event-tickets', onMetaDelete );
		}
	}, dependencies );
}
