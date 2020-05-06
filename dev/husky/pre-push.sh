#!/usr/bin/env bash

# Get the git branch name.
local_branch="$(git rev-parse --abbrev-ref HEAD)"

# Prefixes in a list for easier management.
# These are all the ones from commitizen/convential changelog plus a few we use internally.
valid_prefixes="bucket|build|feature|fix|chore|ci|docs|style|refactor|release|revert|performance|test"

# Regex FTW!
valid_branch_regex="^($valid_prefixes)\/([[:alpha:]]{2,}-[[:digit:]]{2,})"

# Multiline error message.
message="There is something wrong with your branch name!
Branch names in this project must begin with one of the following:
$valid_prefixes, followed by a forward-slash '/' and the Jira ticket number in the format '[[:alpha:]]{2,}-[[:digit:]]{2,}' (ex: feature/ET-01).

Your push has been rejected. You should rename your branch to a valid name and try again."

# Logic.
if [[ ! $local_branch =~ $valid_branch_regex ]]
then
    echo "$message"
	# Return 1 to cancel the push - any non-zero value will do.
    exit 1
fi

# Return 0 to continue the push.
exit 0
