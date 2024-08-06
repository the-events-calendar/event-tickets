import {
	fetchAttendees,
	fetchAndSendAttendeeBatch,
	sendAttendeesToService,
} from '@tec/tickets/seating/admin/seatsReport';
import { ACTION_FETCH_ATTENDEES } from '@tec/tickets/seating/ajax';
import { OUTBOUND_EVENT_ATTENDEES } from '@tec/tickets/seating/service/api/service-actions';

const apiModule = require('@tec/tickets/seating/service/api');

require('jest-fetch-mock').enableMocks();

function getMockAttendees(count) {
	const attendees = [];

	for (let i = 0; i < count; i++) {
		attendees.push({
			id: i,
			name: `Test Attendee ${i}`,
			purchaser: {
				id: i,
				email: `test-${i}@test.com`,
				associatedAttendees: 0,
			},
			ticketId: i,
			seatTypeId: `uuid-seat-type-${i}`,
			seatLabel: `A-${i}`,
			reservationId: `uuid-reservation-${i}`,
		});
	}

	return attendees;
}

describe('Seats Report', () => {
	beforeEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();

		// Mock the service functionality to establish readiness.
		apiModule.establishReadiness = jest.fn(() => true);
	});

	afterEach(() => {
		fetch.resetMocks();
		fetch.enableMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	describe('fetchAttendees', () => {
		it('should fetch first batch if currentBatch is not set', async () => {
			const mockResponse = {
				attendees: getMockAttendees(2),
				currentBatch: 1,
				totalBatches: 2,
				nextBatch: 2,
			};
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ data: mockResponse })
			);

			const result = await fetchAttendees();

			expect(result).toMatchObject(mockResponse);
		});

		it('should fetch the specified batch', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ data: {} })
			);

			// Fetch batch 1.
			await fetchAttendees(1);

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&postId=17&action=${ACTION_FETCH_ATTENDEES}&currentBatch=1`,
				{
					headers: {
						Accept: 'application/json',
					},
				}
			);

			// Fetch batch 2.
			await fetchAttendees(2);

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&postId=17&action=${ACTION_FETCH_ATTENDEES}&currentBatch=2`,
				{
					headers: {
						Accept: 'application/json',
					},
				}
			);

			// Fetch batch 3.
			await fetchAttendees(3);

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&postId=17&action=${ACTION_FETCH_ATTENDEES}&currentBatch=3`,
				{
					headers: {
						Accept: 'application/json',
					},
				}
			);
		});

		it('should returns empty array if no attendees', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data: {
						attendees: [],
						totalBatches: 1,
						currentBatch: 1,
						nextBatch: false,
					},
				})
			);

			const result = await fetchAttendees(1);

			expect(result).toMatchObject({
				attendees: [],
				totalBatches: 1,
				currentBatch: 1,
				nextBatch: false,
			});
		});

		it('should return correctly formed array on bad reponse data', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data: {
						foo: 'bar',
					},
				})
			);

			const result = await fetchAttendees(1);

			expect(result).toMatchObject({
				attendees: [],
				totalBatches: 1,
				currentBatch: 1,
				nextBatch: false,
			});
		});

		it('should throw on response status not 200', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data: {
						success: false,
					},
				}),
				{ status: 400 }
			);

			await expect(fetchAttendees(1)).rejects.toThrow();
		});
	});

	describe('fetchAndSendAttendeeBatch', () => {
		it('should fetch and send attendees correctly with one batch', async () => {
			const iframe = {
				closest: jest.fn().mockReturnValue({
					dataset: {
						token: 'test-token',
					},
				}),
				contentWindow: {
					postMessage: jest.fn(),
				},
			};
			const mockAttendees = getMockAttendees(2);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data: {
						attendees: mockAttendees,
						totalBatches: 1,
						currentBatch: 1,
						nextBatch: false,
					},
				})
			);

			const resolve = jest.fn();

			await fetchAndSendAttendeeBatch(iframe, 1, resolve, 0);

			expect(resolve).toBeCalledWith(2);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees,
						totalBatches: 1,
						currentBatch: 1,
					},
				},
				'https://wordpress.test'
			);
		});

		it('should fetch and send attendees correctly with many batches', async () => {
			const iframe = {
				closest: jest.fn().mockReturnValue({
					dataset: {
						token: 'test-token',
					},
				}),
				contentWindow: {
					postMessage: jest.fn(),
				},
			};
			const mockAttendees = getMockAttendees(5);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				async (req) => {
					const url = new URL(req.url);
					const currentBatch = url.searchParams.get('currentBatch');

					if (currentBatch === '1') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(0, 2),
								totalBatches: 3,
								currentBatch: 1,
								nextBatch: 2,
							},
						});
					} else if (currentBatch === '2') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(2, 4),
								totalBatches: 3,
								currentBatch: 2,
								nextBatch: 3,
							},
						});
					} else if (currentBatch === '3') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(4, 6),
								totalBatches: 3,
								currentBatch: 3,
								nextBatch: false,
							},
						});
					}
				}
			);

			const resolve = jest.fn();

			await fetchAndSendAttendeeBatch(iframe, 1, resolve, 0);

			expect(resolve).toBeCalledWith(5);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(0, 2),
						totalBatches: 3,
						currentBatch: 1,
					},
				},
				'https://wordpress.test'
			);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(2, 4),
						totalBatches: 3,
						currentBatch: 2,
					},
				},
				'https://wordpress.test'
			);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(4, 46),
						totalBatches: 3,
						currentBatch: 3,
					},
				},
				'https://wordpress.test'
			);
		});
	});

	describe('sendAttendeesToService', () => {
		it('should send attendees correctly with one batch', async () => {
			const iframe = {
				closest: jest.fn().mockReturnValue({
					dataset: {
						token: 'test-token',
					},
				}),
				contentWindow: {
					postMessage: jest.fn(),
				},
			};
			const mockAttendees = getMockAttendees(2);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data: {
						attendees: mockAttendees,
						totalBatches: 1,
						currentBatch: 1,
						nextBatch: false,
					},
				})
			);

			const sent = await sendAttendeesToService(iframe);

			expect(sent).toBe(2);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees,
						totalBatches: 1,
						currentBatch: 1,
					},
				},
				'https://wordpress.test'
			);
		});

		it('should send attendees correctly with many batches', async () => {
			const iframe = {
				closest: jest.fn().mockReturnValue({
					dataset: {
						token: 'test-token',
					},
				}),
				contentWindow: {
					postMessage: jest.fn(),
				},
			};
			const mockAttendees = getMockAttendees(5);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				async (req) => {
					const url = new URL(req.url);
					const currentBatch = url.searchParams.get('currentBatch');

					if (currentBatch === '1') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(0, 2),
								totalBatches: 3,
								currentBatch: 1,
								nextBatch: 2,
							},
						});
					} else if (currentBatch === '2') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(2, 4),
								totalBatches: 3,
								currentBatch: 2,
								nextBatch: 3,
							},
						});
					} else if (currentBatch === '3') {
						return JSON.stringify({
							success: true,
							data: {
								attendees: mockAttendees.slice(4, 6),
								totalBatches: 3,
								currentBatch: 3,
								nextBatch: false,
							},
						});
					}
				}
			);

			const sent = await sendAttendeesToService(iframe);

			expect(sent).toBe(5);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(0, 2),
						totalBatches: 3,
						currentBatch: 1,
					},
				},
				'https://wordpress.test'
			);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(2, 4),
						totalBatches: 3,
						currentBatch: 2,
					},
				},
				'https://wordpress.test'
			);
			expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
				{
					action: OUTBOUND_EVENT_ATTENDEES,
					token: 'test-token',
					data: {
						attendees: mockAttendees.slice(4, 46),
						totalBatches: 3,
						currentBatch: 3,
					},
				},
				'https://wordpress.test'
			);
		});
	});
});
