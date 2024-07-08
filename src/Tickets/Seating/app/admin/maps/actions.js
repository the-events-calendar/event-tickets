import {ajaxUrl, ajaxNonce} from "../../service/service-api/externals";
import {onReady} from "../../utils";

const register_delete_action = () => {
	// add click listener to all links with class 'delete'
	document.querySelectorAll('.delete-map').forEach((link) => {
		link.addEventListener('click', async ( event ) => {
			event.preventDefault();
			await handleDelete( event );
		});
	});
}

const handleDelete = async ( event ) => {
	// get the data-map-id from the link.
	const mapId = event.target.getAttribute('data-map-id');
	if ( confirm('Are you sure you want to delete this map?') ) {
		const result = await delete_map( mapId );
		if ( result ) {
			window.location.reload();
		} else {
			console.log('failed to delete');
			alert( 'Failed to delete the map' );
		}
	} else {
		console.log('not deleted');
	}
}

const delete_map = async (mapId) => {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_delete_map'
	);
	const response = await fetch( url.toString(), { method: 'POST' } );

	return response.status === 200;
}

onReady(register_delete_action);