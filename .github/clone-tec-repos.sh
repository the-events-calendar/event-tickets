#!/usr/bin/env bash

set -e

suite_file=$(dirname "$(dirname "$0")")"/"$1
branch=$2

if [ ! -f "$suite_file" ]; then
  echo "Suite file not found"
  exit 1
fi

# Parse the tec_plugins line in the suite file and create an array
# The line is expected to be a comma-separated (space optional) list of plugins.
# E.g. `tec_plugins: plugin-1, plugin-2,plugin-3, plugin-4`.
tec_plugins=$(grep tec_plugins "$suite_file" | awk -F ': ' '{print $2}' | sed 's/,\s*/ /g')

# If there are no plugins to clone, exit 0.
if [ -z "$tec_plugins" ]; then
  echo "No plugins to clone"
  exit 0
fi

# Echo a message to list the plugins to be cloned.
echo "Plugins to clone: $tec_plugins"

if [ -z "${GH_BOT_TOKEN}" ]; then
  echo "Token is empty"
  exit 1
fi

# Foreach plugin, clone it using the GitHub API, the token is stored in env, under GH_BOT_TOKEN.
for plugin in $tec_plugins; do
  plugin_repository="https://$GH_BOT_TOKEN@github.com/the-events-calendar/$plugin.git"
  plugin_directory="$(pwd)/$plugin"

  branch_exists=0
  if [ -n "$branch" ]; then
    branch_exists=$(git ls-remote --heads "$plugin_repository" "$branch" | wc -l)
  fi

  # If the branch exists, clone that, else clone the default branch.
  if [ "$branch_exists" -eq 0 ]; then
    echo "Branch default branch of plugin $plugin into '$plugin_directory' ..."
    git clone "$plugin_repository" --single-branch || exit 1
  else
    echo "Cloning branch $branch of plugin $plugin into '$plugin_directory' ..."
    git clone "$plugin_repository" --branch "$branch" || exit 1
  fi

  # Change directory to the plugin and init the submodules.
  echo "Initializing submodules for plugin $plugin into '$plugin_directory'..."
  cd "$plugin" || exit 1
  git submodule update --init --recursive --single-branch || exit 1
  cd ..
done
