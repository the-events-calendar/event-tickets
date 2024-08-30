# Seating End-to-End Tests

This directory contains the end-to-end tests for the Seating feature, the tests use Playwright to run the tests in a headless Chrome browser.

## Local setup and use

To run the tests locally, you will need [`slic`][1] up and running.
Assuming `slic` in your `$PATH`, you can run the following commands to start the container and run the tests:

```bash
# Start the container
slic up

# Install Playwright dependencies if required
slic playwright install

# Run the tests
slic playwright test tests/end-to-end/seating
```

### Updating snapshots

If you need to update the snapshots, you can run the following command:

```bash
slic playwright test tests/end-to-end/seating --update-snapshots
```

Or the short version:

```bash
slic playwright test tests/end-to-end/seating -u
```

## CI

The tests are run in the CI pipeline, see the [`.github/workflows/tests-end-to-end-seating.yml`](../../../.github/workflows/tests-end-to-end-seating.yml) file for the details.

[1]: https://github.com/stellarwp/slic
