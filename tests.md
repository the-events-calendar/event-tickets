# Quick Tests Introduction

This is a brief and quick guide that covers the bare essentials needed to set up PHP and JS tests on your local plugin copy.
Please refer to [Codeception](http://codeception.com/docs) and [WP Browser](https://github.com/lucatume/wp-browser) documentation for any PHP test issues or [Jest](https://jestjs.io/docs/en/getting-started) for any JS test issues that are not ET related.

## PHP Tests

### Set up
After cloning the ET repository on your local machine change directory to the plugin root folder and pull in any needed dependency using [Composer](https://getcomposer.org/):

	composer install

Using `composer install` in place of `composer update` will ensure you are using working and known dependencies; only run `composer update` if you know what you are doing.
When Composer finished the update process (might take a while) set up your own [Codeception](http://codeception.com/) installation to work in your local setup.
Create a `codeception.yml` file in the plugin root folder with this content:
_(note: if you copy/paste this, make sure it's using spaces and not tabs!)_

```yaml
params:
  - .env.testing.local
```

Codeception will process configuration files in a cascading way, think of CSS, so the `codeception.dist.yml` file will be read first and whatever you set in `codeception.yml` will be applied on top of it.
The only override we do here is telling Codeception that it should read the modules settings not from the `.env` file, that is configured to run the tests on Travis CI, but to read them from the `.env.testing.local` file.
Now create, again in the plugin root folder, a `.env.testing.local` file copying the `.env.` file and changing any value in it to match your local installation, e.g.:

```
WP_ROOT_FOLDER="/Users/Luca/Sites/wp"
WP_DOMAIN="tribe.test"
WP_URL="http://tribe.test"
WP_ADMIN_USERNAME="admin"
WP_ADMIN_PASSWORD="secret"
WP_DB_HOST="db"
WP_DB_NAME="tribe"
WP_DB_USER="root"
WP_DB_PASSWORD="root"
WP_TEST_DB_HOST="db"
WP_TEST_DB_NAME="test"
WP_TEST_DB_USER="root"
WP_TEST_DB_PASSWORD="root"
```

If you look at any `tests/*.suite.dist.yml` file you will see that the configuration contains placeholders like `%WP_ROOT_FOLDER%` that [Codeception will configure at runtime](http://codeception.com/docs/06-ModulesAndHelpers#Dynamic-Configuration-With-Parameters).
Once those are correct you are ready to run, no need to change anything else.

#### Errors with populator
The `restv1` test suite uses [the Codeception `populator` functionality](https://codeception.com/docs/modules/Db#Populator); this means that that the database dump is imported not using PHP code but using the `mysql` command.
It's highly probable that, depending on your host machine and configuration you will need to either:
* not use `populator` - create a copy of the `/tests/restv1.suite.dist.yml` and call it `/tests/restv1.suite.yml`; in that file remove the `WPDb > populator` entry.
* configure the `populator` parameter to work differently - create a copy of the `/tests/restv1.suite.dist.yml` and call it `/tests/restv1.suite.yml`; in that file edit the `WPDb > populator` entry to suite your local environment.

### Running the tests
Nothing different from a default Codeception environment so this command will run all the tests in the `wpunit` suite:

```bash
vendor/bin/codecept run wpunit
```

**Do not** run all the suites at the same time using `vendor/bin/codecept run`: due to WordPress love for globals and side-effects this will mean disaster.
To run a specific test case (a `class`) use:

```bash
vendor/bin/codecept run tests/wpunit/Some/Path/MyTest.php
```

To run a single test method (a `function`) in a test case use:

```bash
vendor/bin/codecept run tests/wpunit/Some/Path/MyTest.php:some_test
```

Failing tests are ok in set up terms: the system works. Errors should be reported.
Please refer to [Codeception documentation](http://codeception.com/docs) to learn about more run and configuration options.

### Where to find help
Look for test examples in the code; look for configuration guides on [Codeception](http://codeception.com/ "Codeception - BDD-style PHP testing.") and [wp-browser](https://github.com/lucatume/wp-browser "lucatume/wp-browser · GitHub")  site; ask for help to other testers for things like "How should I test this?" or "In what suite should I add this test?".

## JS Tests

### Set up
After cloning the ET repository on your local machine change directory to the plugin root folder. Ensure that you have `node`, `npm`, and `nvm` installed and are using the correct `node` version. If you are using an incorrect version of `node`, you will receive an error. In this case, run:

```bash
nvm install <version>
```

Where `<version>` is the node version that was specified. This version can also be found in `.nvmrc`. Once you have the correct node version, check that you are using the correct version:

```bash
nvm ls
```

There will be an arrow pointing to the version number. If you are not using the correct version, run:

```bash
nvm use <version>
```

where `<version>` is the version number. Once that is set, run:

```bash
npm install
```

This will install all the packages required.

### Running the tests
To run the tests, simply use:

```bash
npm run test
```

This will run all JS tests in the plugin (including `common`). If you want to run a specific test or group of tests, you can do the following:

```bash
npm run test -- path/to/test
npm run test -- path/to/first/test path/to/second/test
npm run test -- path/to/specific/test/file.test.js
```

Jest matches the pattern supplied to the path to each test. If there is a match, Jest will run the test.

Some tests may fail due to snapshots not matching, this is OK. You can fix this by running:

```bash
npm run test -- -u path/to/test
```

**NOTICE:** Do not run the above script without confirming first which snapshots will be updated. If updated without confirming, incorrect snapshots could be stored and faulty test results could produce a passing test.

### Where to find help
Look at example tests in the code to write a specific test. You can also find more information from [Jest](https://jestjs.io/docs/en/getting-started) or [Enzyme](https://airbnb.io/enzyme/docs/api/) on writing tests.
