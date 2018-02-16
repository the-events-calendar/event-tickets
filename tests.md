# Quick tests introduction

This is a brief and quick guide that's covering the bare essentials needed to set up the tests on your local plugin copy.
Please refer to [Codeception](http://codeception.com/docs) and [WP Browser](https://github.com/lucatume/wp-browser) documentation for any issue that's not TEC related.

## Set up
After cloning the TEC repository on your local machine change directory to the plugin root folder and pull in any needed dependency using [Composer](https://getcomposer.org/):

	composer install

Using `composer install` in place of `composer update` will ensure you are using working and known dependencies; only run `composer update` if you know what you are doing.  
When Composer finished the update process (might take a while) set up your own [Codeception](http://codeception.com/) installation to work in your local setup.  
Create a `codeception.yml` file in the plugin root folder with this content:

```yaml
params:
	- .env.local
```

Codeception will process configuration files in a cascading way, think of CSS, so the `codeception.dist.yml` file will be read first and whatever you set in `codeception.yml` will be applied on top of it.  
The only override we do here is telling Codeception that it should read the modules settings not from the `.env` file, that is configured to run the tests on Travis CI, but to read them from a `.env.local` file.  
Now create, again in the plugin root folder, a `.env.local` file copying the `.env.` file and changing any value in it to match your local installation, e.g.:

```
WP_ROOT_FOLDER="/Users/luca/Local Sites/commerce/app/public"
WP_DOMAIN="commerce.dev"
WP_URL="http://commerce.dev"
WP_ADMIN_USERNAME="admin"
WP_ADMIN_PASSWORD="admin"
WP_ADMIN_PATH="/wp-admin"
DB_HOST="192.168.92.100:4010"
DB_NAME="local"
DB_USER="root"
DB_PASSWORD="root"
TEST_DB_HOST="192.168.92.100:4010"
TEST_DB_NAME="tests"
TEST_DB_USER="root"
TEST_DB_PASSWORD="root"
```

If you look at any `tests/*.suite.dist.yml` file you will see that the configuration contains placeholders like `%WP_ROOT_FOLDER%` that [Codeception will configure at runtime](http://codeception.com/docs/06-ModulesAndHelpers#Dynamic-Configuration-With-Parameters).  
Once those are correct you are ready to run, no need to change anything else.
	
## Running the tests
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

## Where to find help
Look for test examples in the code; look for configuration guides on [Codeception](http://codeception.com/ "Codeception - BDD-style PHP testing.") and [wp-browser](https://github.com/lucatume/wp-browser "lucatume/wp-browser · GitHub")  site; ask for help to other testers for things like "How should I test this?" or "In what suite should I add this test?".  

## Writing acceptance tests

### What is an acceptance test?
A test in which you exercise the UI like a user would, set some pre-conditions (e.g. a database entry) and make assertions like a user **with confidence with her browser developer tools** would do.
See the `tests/acceptance` folder for examples and then get back here. Really: do it.

### Do I need to know PHP?
To a basic level, yes.
Since you've looked at the examples you know it's not rocket science.

### What about the developer tools...?
It's important is that you understand that **you should never check for strings** unless there is a very good reason (e.g. localization testing).  
What does this mean? If in a test you have to click a button and write:

```php
$I->click( 'Save' );
```

then that test will fail if the button text is updated. And **it will be updated** sooner or later.  
Check instead for that button [CSS selector](https://www.w3schools.com/cssref/css_selectors.asp) (jQuery-like) or [XPath selector](https://www.w3schools.com/xml/xpath_syntax.asp).  
How do you get that? Using your browser developer tools.

### But this is too difficult...
Maybe. What's certain is that you are now in the 1% of people that can write acceptance tests.

### What if I do not know how to do something?
Google is your friend; as noted above [Codeception](http://codeception.com/docs) and [WP Browser](https://github.com/lucatume/wp-browser) documentation will help.  
If nothing of the above works ask a developer.

### Setting up to write acceptance tests
Same as the "Set up" section above. You can do it; I (a mindless file) believe in you.  
If stuck see the point above.

### Cept, Cest...?
the `Cept` kind of tests are easier to read but do not allow for reuse of code; `Cest` tests allow instead for `_before` and `_after` methods (and more) to store re-usable code.  
This is, by no means all, [see more here](https://codeception.com/docs/02-GettingStarted#Cept-Cest-and-Test-Formats).  

### How do I create new test?
From the plugin root folder:

* cept format - `vendor/bin/codecept generate:cept acceptance "Admin\Settings\SomeTest"`
* cest format - `vendor/bin/codecept generate:cest acceptance "Admin\Settings\SomeTest"`

Try to keep the tests neatly organized in folders using the format above unless it's **really** a special case (e.g. plugin activation).
