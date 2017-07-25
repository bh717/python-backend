#!/usr/bin/env bash
set -e

# This script can be configured by specifying different environment variables in
# your .gitlab-ci.yml file's invocation of the script. If those are omitted, as
# in this example, the defaults below and throughout the script should be used.

# Check basic requirements from Config.
if [ -z "$PLATFORM_PROJECT_ID" ]; then
  echo "PLATFORM_PROJECT_ID is required, please contact support if you don't know how to do it."
  exit 1
fi

# By default we use master as the Platform parent env.
PF_PARENT_ENV=${PF_PARENT_ENV:-master}

# By default we don't allow master to be deployed.
ALLOW_MASTER=${ALLOW_MASTER:-0}

# Prepare the variables.
PF_BRANCH=${PF_DEST_BRANCH:-$CI_BUILD_REF_NAME}

# Platform command path.
CLI_CMD=${CLI_CMD:-"$HOME/.platformsh/bin/platform --yes"}

if [ -z "$PF_BRANCH" ]; then
  echo "Source branch (CI_BUILD_REF_NAME or PF_DEST_BRANCH) not defined."
  exit 1
fi

# This script is not for production deployments.
if [ "$PF_BRANCH" = "master" ] && [ "$ALLOW_MASTER" != 1 ]; then
  echo "Not pushing master branch."
  exit
fi

# Set the project for further CLI commands.
COMMAND_SET_REMOTE="${CLI_CMD} project:set-remote ${PLATFORM_PROJECT_ID}"
eval $COMMAND_SET_REMOTE

# Push to PS.
COMMAND_PUSH="${CLI_CMD} push --verbose --force --target=${PF_BRANCH}"
if [ "$PF_PARENT_ENV" != "$PF_BRANCH" ]; then
  COMMAND_PUSH="$COMMAND_PUSH --activate --parent=${PF_PARENT_ENV}"
fi
eval $COMMAND_PUSH

# Clean up already merged and inactive environments.
COMMAND_CLEANUP="${CLI_CMD} environment:delete  --verbose --inactive --merged --environment=${PF_PARENT_ENV} --exclude=master --exclude="${PF_BRANCH}" --yes --delete-branch --no-wait || true"
eval $COMMAND_CLEANUP

# Store url of new environment in a file
${CLI_CMD} environment:url -e $CI_BUILD_REF_NAME --pipe | head -1 > scripts/platform.url
