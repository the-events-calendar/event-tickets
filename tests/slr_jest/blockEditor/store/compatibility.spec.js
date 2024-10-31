import { currentProviderSupportsSeating } from '@tec/tickets/seating/blockEditor/store/compatibility';
import commonStoreBridge from '@tec/tickets/seating/blockEditor/store/common-store-bridge';
jest.mock('@tec/tickets/seating/blockEditor/store/common-store-bridge', () => ({
	getTicketProviderFromCommonStore: jest.fn(),
}));

describe('compatibility.js', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.restoreAllMocks();
	});

	describe('currentProviderSupportsSeating', () => {
		test('returns false if current provider is empty string', () => {
			commonStoreBridge.getTicketProviderFromCommonStore.mockReturnValue(
				''
			);

			expect(currentProviderSupportsSeating()).toBe(false);
		});

		test('returns true if current provider is Tickets Commerce', ()=>{
			commonStoreBridge.getTicketProviderFromCommonStore.mockReturnValue(
				'TEC\\Tickets\\Commerce\\Module'
			);

			expect(currentProviderSupportsSeating()).toBe(true);
		});

		test('returns true if current provider is not Tickets Commerce', () => {
			commonStoreBridge.getTicketProviderFromCommonStore.mockReturnValue(
				'Some__Other__Provider'
			);

			expect(currentProviderSupportsSeating()).toBe(false);
		});
	});
});
