# Quick Introduction

This is a guide to building and compiling assets locally. The process is automated for deployments, so you should not have to worry about that. It borrows heavily from tests.md as they share some setup processes, so there will be some overlap.

## Set up

After cloning the ET repository on your local machine, change directory to the plugin root folder and ensure that you have the latest `tribe-common` by running `git submodule upate --init --recursive`. It is also a good idea to do tis when you change branches - particularly when changing release branch root.

### Composer

Then, pull in any needed dependency using [Composer](https://getcomposer.org/):

	composer install

Using `composer install` in place of `composer update` will ensure you are using working and known dependencies; only run `composer update` if you know what you are doing!

Once Composer is done, run `npm install` to ensure you have the correct node modules for your build processes.

_Note: all items in /vendor have been ignored, so if you need to add something of ours there, ensure that you are using Comppsoer to pull it in!_

### Assets

Compiled assets are NOT to be committed to the repo! They create extra conflicts and are regenerated on deploy anyway. Help keep our repos as small and simple as possbile!

After running `npm install`, you can compile assets by running `npm run build` In fact, you'll **need to** if you expect to load the site locally, as the css and js files are not in their final state. You will also need to any time you make changes to the pre-compiled code (js, css, etc) to view/test locally.

### Husky

NPM will install [Husky](https://github.com/typicode/husky), which allows us to easily run scripts on git hooks. We've included a `.huskyrc.sample` file - to use Husky locally, duplicate this file and rename to `.huskyrc`. Then edit it to your heart's content!

_Don't worry - it's already in `.gitignore` to prevent accidentally committing it! We do, however, reserve the right to change that in the future to enforce some standardization._

#### Git Hook Scripts

There is a sample hook in `.huskyrc.sample` that runs a script on post-checkout. The script is defined in `package.json`. For custom scripts beyond running the commands in `package.json`, you can add (and .gitignore) a script file in the base directory. So if you have some complex js to validate commit messagesw (for example), you can put it in my-validate.js and then reference it thus in `.huskyrc`:

	"husky": {
	    "hooks": {
	        "commit-msg": "node my-validate.js"
	    }
	},

If you have several, consider putting them all in one `.gitignore`-d folder and reference them by path.

If you think this stuff is really cool, I recommend reading the [Husky docs](https://github.com/typicode/husky/blob/master/DOCS.md)
