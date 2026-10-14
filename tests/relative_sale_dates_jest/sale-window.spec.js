/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone Pacific/Auckland
 */
import { describeSharedFixtureCases } from './sale-window-cases';

// New Zealand Standard Time, 12 hours ahead of UTC in June, far from every fixture timezone.
describeSharedFixtureCases( -720 );
