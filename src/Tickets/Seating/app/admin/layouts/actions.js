import {ajaxUrl, ajaxNonce} from "../../service/service-api/externals";
import { onReady } from "../../utils";

const register_delete_action = () => {
	// add click listener to all links with class 'delete'
	document.querySelectorAll('.delete-layout').forEach((link) => {
		link.addEventListener('click', async ( event ) => {
			event.preventDefault();
			await handleDelete( event );
		});
	});
}

const handleDelete = async ( event ) => {
	// get the data-layout-id from the link.
	const layoutId = event.target.getAttribute('data-layout-id');
	const mapId = event.target.getAttribute('data-map-id');

	const card = event.target.closest('.tec-tickets__seating-tab__card');
	card.style.opacity = 0.5;

	if ( confirm('Are you sure you want to delete this layout?') ) {
		const result = await delete_layout( layoutId, mapId );
		if ( result ) {
			window.location.reload();
		} else {
			card.style.opacity = 1;
			alert( 'Failed to delete the layout' );
		}
	} else {
		card.style.opacity = 1;
	}
}

const delete_layout = async (layoutId, mapId) => {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('layoutId', layoutId);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_delete_layout'
	);
	const response = await fetch( url.toString(), { method: 'POST' } );

	return response.status === 200;
}

onReady(register_delete_action);