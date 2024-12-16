import { getTicketProviderFromCommonStore } from './common-store-bridge';

/**
 * Returns whether the current ticket provider supports Fees or not.
 *
 * This value cannot be read from data localized by the backend since the user
 * will be able to change the ticket provider live, while the post, or Ticket,
 * editing is happening.
 *
 * @since 5.18.0
 *
 * @return {boolean} Whether the current ticket provider supports fees or not.
 */
export function currentProviderSupportsFees() {
	const provider = getTicketProviderFromCommonStore();

	return 'TEC\\Tickets\\Commerce\\Module' === provider;
}
