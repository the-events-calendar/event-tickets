import { actions } from '@tec/tickets/seating/blockEditor/store/actions';

// IMPORTANT!
// Remember if these tests need to change we should probably also update store's reducer method.!
describe('actions', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('should have the correct actions', () => {
		expect(actions).toMatchSnapshot();
	});

	it('every action should return what is expected', () => {
		const testData = ['test-1', 'test-2', 'test-3'];
		for (const action in actions) {
			expect(actions[action](...testData)).toMatchSnapshot();
		}
	});
});
