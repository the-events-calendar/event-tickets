import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

// Load .env.testing.slic (CI defaults) — process.env takes precedence for local overrides.
require('dotenv').config({ path: path.resolve(__dirname, '.env.testing.slic') });

const baseURL = process.env.WP_URL || 'http://wordpress.test';

// Skip destructive DB operations when running locally (not in CI).
if (!process.env.CI) {
	process.env.SKIP_DB_RESET = '1';
}

export default defineConfig({
	testDir: './tests/end-to-end',
	outputDir: './tests/_output/playwright',
	preserveOutput: 'failures-only',
	snapshotPathTemplate: '{testDir}/{testFileDir}/__screenshots__/{testName}-{arg}{ext}',
	updateSnapshots: 'missing',
	/* Run tests in files in parallel? No, we want to run them in order since they are insisting on the same database. */
	fullyParallel: false,
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !!process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: 'list',
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		baseURL,
		ignoreHTTPSErrors: baseURL.startsWith('https://'),
		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'on-first-retry',
	},
	/* Configure projects for major browsers, using only chromium */
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
});
