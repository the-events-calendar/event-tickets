import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import { addQueryArgs } from '@wordpress/url';
import { getCurrencySettings } from '../localizedData.ts';
import { GetTicketApiResponse, GetTicketsApiResponse, GetTicketsApiParams, UpsertTicketApiRequest } from '../types/Api';
import { CostDetails } from '../types/CostDetails';
import { CapacitySettings, FeesData, SalePriceDetails, TicketSettings, TicketType } from '../types/Ticket';

const apiBaseUrl = '/tec/v1/tickets';

/**
 * Fetch tickets from the API.
 *
 * This function retrieves tickets based on the provided parameters. It  will return a promise that resolves to
 * the tickets response. If there are errors with the request, it will reject with an error message. The calling code
 * should handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {GetTicketsApiParams} params Optional parameters for the API request.
 * @return {Promise<GetTicketsApiResponse>} A promise that resolves to the tickets response.
 */
export const fetchTickets = async ( params: GetTicketsApiParams = {} ): Promise< GetTicketsApiResponse > => {
	const queryArgs: GetTicketsApiParams = {};

	if ( params.event ) {
		queryArgs.event = params.event;
	}

	if ( params.per_page ) {
		queryArgs.per_page = params.per_page;
	}

	if ( params.page ) {
		queryArgs.page = params.page;
	}

	const path = addQueryArgs( apiBaseUrl, queryArgs );

	return new Promise< GetTicketsApiResponse >( async ( resolve, reject ) => {
		await apiFetch( { path: path } )
			.then( ( data ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject( new Error( 'Failed to fetch tickets: response did not return an object.' ) );
				} else {
					resolve( data as GetTicketsApiResponse );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to fetch tickets: ${ error.message }` ) );
			} );
	} );
};

/**
 * Fetch tickets for a specific post ID.
 *
 * This function retrieves tickets associated with a specific post ID. It will return a promise that resolves to
 * an array of tickets. If there are errors with the request, it will reject with an error message. The calling code
 * should handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {number} postId The ID of the post to fetch tickets for.
 * @return {Awaited<TicketSettings[]>} A promise that resolves to an array of tickets.
 */
export const fetchTicketsForPost = async ( postId: number ): Promise< TicketSettings[] > => {
	return new Promise< TicketSettings[] >( async ( resolve, reject ) => {
		// todo: Handle the potential for multiple pages of results.
		await fetchTickets( { event: postId } )
			.then( ( response: GetTicketsApiResponse ) => {
				resolve( response.map( ( ticket: GetTicketApiResponse ) => mapApiResponseToTicketSettings( ticket ) ) );
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to fetch tickets for post ID ${ postId }: ${ error.message }` ) );
			} );
	} );
};

/**
 * Create or update a ticket.
 *
 * This function will create a new ticket or update an existing one based on whether the ticket data
 * contains an ID greater than 0. It returns a promise that resolves to the created or updated ticket.
 * If there are errors with the request, it will reject with an error message. The calling code should
 * handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {TicketSettings} ticketData The data for the ticket to create or update.
 * @return {Promise<TicketSettings>} A promise that resolves to the created or updated ticket.
 */
export const upsertTicket = async ( ticketData: TicketSettings ): Promise< TicketSettings > => {
	const isUpdate: boolean = Boolean( ticketData.id && ticketData.id > 0 );

	return new Promise< TicketSettings >( async ( resolve, reject ) => {
		await apiFetch( {
			path: `${ apiBaseUrl }${ isUpdate ? `/${ ticketData.id }` : '' }`,
			method: isUpdate ? 'PUT' : 'POST',
			data: mapTicketSettingsToApiRequest( ticketData, isUpdate ),
		} )
			.then( ( data: GetTicketApiResponse ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject(
						new Error(
							`Failed to ${ isUpdate ? 'update' : 'create' } ticket: response did not return an object.`
						)
					);
				} else {
					resolve( mapApiResponseToTicketSettings( data ) );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to ${ isUpdate ? 'update' : 'create' } ticket: ${ error.message }` ) );
			} );
	} );
};

/**
 * Delete a ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to delete.
 * @return {Promise<void>} A promise that resolves when the ticket is deleted.
 * @throws {Error} If the deletion fails.
 */
export const deleteTicket = async ( ticketId: number ): Promise< void > => {
	return new Promise< void >( async ( resolve, reject ) => {
		await apiFetch( {
			path: `${ apiBaseUrl }/${ ticketId }`,
			method: 'DELETE',
		} )
			.then( () => {
				resolve();
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to delete ticket: ${ error.message }` ) );
			} );
	} );
};

/**
 * Maps ticket settings data to the structure required for an API request.
 *
 * @since TBD
 *
 * @param {TicketSettings} ticketData The ticket settings data object containing ticket details such as name, price, capacity, and sale price information.
 * @param {boolean} isUpdate Indicates whether the operation is an update (`true`) or create (`false`) request.
 * @returns {UpsertTicketApiRequest} The formatted API request object based on the provided ticket settings.
 */
const mapTicketSettingsToApiRequest = ( ticketData: TicketSettings, isUpdate: boolean ): UpsertTicketApiRequest => {
	// Map basic ticket information.
	const body: UpsertTicketApiRequest = {
		title: ticketData.name || '',
		content: ticketData?.description || '',
		event: ticketData.eventId,
		price: ticketData.costDetails?.value || 0,
		type: ticketData?.type || 'default',
		show_description: true,
	};

	// Map capacity and stock settings.
	if ( ticketData.capacitySettings ) {
		const { enteredCapacity = '' } = ticketData.capacitySettings;

		// If the Capacity is set, convert it to number. Otherwise, don't add it to the API request.
		if ( '' !== enteredCapacity ) {
			const capacityNumber = Number( enteredCapacity );
			body.capacity = capacityNumber;
			body.stock = capacityNumber;
		}
	}

	// Map sale dates
	if ( ticketData.availableFrom ) {
		// Convert to the format expected by the API: "YYYY-MM-DD HH:MM:SS"
		const availableFromDate = new Date( ticketData.availableFrom );
		body.start_date = availableFromDate.toISOString().slice( 0, 19 ).replace( 'T', ' ' );
	}

	if ( ticketData.availableUntil ) {
		// Convert to the format expected by the API: "YYYY-MM-DD HH:MM:SS"
		const availableUntilDate = new Date( ticketData.availableUntil );
		body.end_date = availableUntilDate.toISOString().slice( 0, 19 ).replace( 'T', ' ' );
	}

	// Map sale price data
	if ( ticketData.salePriceData ) {
		const salePriceData = ticketData.salePriceData;

		if ( salePriceData.enabled && salePriceData.salePrice ) {
			body.sale_price = parseFloat( salePriceData.salePrice );
		}

		if ( salePriceData.startDate ) {
			body.sale_price_start_date = salePriceData.startDate;
		}

		if ( salePriceData.endDate ) {
			body.sale_price_end_date = salePriceData.endDate;
		}
	}

	if ( ticketData.menuOrder ) {
		body.menu_order = ticketData.menuOrder;
	}

	if ( ticketData.fees ) {
		// todo: add fees to the API.
	}

	// Set the filter as its own full string, to allow for easier discoverability when searching for it.
	const filterName = isUpdate ? 'tec.classy.tickets.updateTicket' : 'tec.classy.tickets.createTicket';

	/**
	 * Filter the body of the upsert request before sending it to the API.
	 *
	 * @since TBD
	 *
	 * @param {Record<string, any>} body The object containing additional values to be sent in the request.
	 * @param {TicketSettings} ticketData The ticket data being sent.
	 */
	const additionalValues: Record< string, any > = applyFilters( filterName, {}, ticketData ) as Record< string, any >;

	// Append/update additional values in the body.
	Object.entries( additionalValues ).forEach( ( [ key, value ] ) => {
		if ( value !== undefined && value !== null ) {
			body[ key ] = value;
		}
	} );

	return body;
};

/**
 * Map API response to TicketSettings type.
 *
 * @since TBD
 *
 * @param {GetTicketApiResponse} apiResponse The API response for a ticket.
 * @return {TicketSettings} The mapped ticket settings.
 */
const mapApiResponseToTicketSettings = ( apiResponse: GetTicketApiResponse ): TicketSettings => {
	// Map capacity settings based on stock management
	const capacitySettings: CapacitySettings = {
		enteredCapacity: apiResponse.stock || 0,
	};

	// Map sale price data
	const salePriceData: SalePriceDetails = {
		// todo: fix the API to return the 'on_sale' value.
		enabled: apiResponse.on_sale || false,
		salePrice: apiResponse.sale_price?.toString() || '',
		startDate: apiResponse.sale_price_start_date || '',
		endDate: apiResponse.sale_price_end_date || '',
	};

	const costDetails: CostDetails = {
		...getCurrencySettings(),
		value: apiResponse.price,
	};

	// Map available dates
	const availableFrom = apiResponse.start_date || '';
	const availableUntil = apiResponse.end_date || '';

	// @todo: Handle fees and other fields.
	// These are not provided by the API, so we use defaults for now.
	const provider = 'tc';
	const menuOrder = apiResponse.menu_order || 0;

	// Default empty fees structure
	const fees: FeesData = {
		automaticFees: [],
		availableFees: [],
		selectedFees: [],
	};

	/**
	 * Filter the mapped ticket settings before returning.
	 *
	 * @version TBD
	 *
	 * @param {Record<string, any>} ticketSettings The ticket settings object being returned.
	 * @param {GetTicketApiResponse} apiResponse The original API response for the ticket.
	 */
	const additionalValues: Record< string, any > = applyFilters(
		'tec.classy.tickets.mapApiResponseToTicketSettings',
		{},
		apiResponse
	) as Record< string, any >;

	return {
		...additionalValues,
		id: apiResponse.id,
		eventId: apiResponse.event,
		name: apiResponse.title.rendered,
		description: apiResponse.description,
		cost: apiResponse.price.toString(),
		costDetails: costDetails,
		salePriceData: salePriceData,
		capacitySettings: capacitySettings,
		provider: provider,
		type: apiResponse.type as TicketType,
		availableFrom: availableFrom,
		availableUntil: availableUntil,
		menuOrder: menuOrder,
		fees: fees,
	};
};
