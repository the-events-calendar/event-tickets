import { getTicketProviderFromCommonStore } from './common-store-bridge';

/**
 * Returns whether the current ticket provider supports Seating or not.
 *
 * This value cannot be read from data localized by the backend since the user
 * will be able to change the ticket provider live, while the post, or Ticket,
 * editing is happening.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the current ticket provider supports seating or not.
 */
export function currentProviderSupportsSeating() {
	const provider = getTicketProviderFromCommonStore();

	return 'TEC\\Tickets\\Commerce\\Module' === provider;
}
