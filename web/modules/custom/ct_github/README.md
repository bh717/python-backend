# Github Contribution Tracker

This module provides functionality for tracking issue and PR contributions on Github

## Table of Contents

[[_TOC_]]

## Introduction

This module provides a `ContributionSource` plugin which tracks contributions on
GitHub using its GraphQL API. It also provides a field on user entity to store
each user's GitHub username.

## Usage

Once Github Contribution Tracker module is installed, you need to edit the users
and fill the github username in the field "Github Username" for whom you need to
track contribution.

On the next cron run, the system will fetch all the users with this field set.
For each user, it will use track all their latest contribution on Github on
contrib-tracker.

### Composer dependencies

Since this module depends on a PHP package to use GitHub API, this module's
composer.json must be included in the site's composer.json file. The recommended
way to do this is by using a [path repository](https://www.drupal.org/docs/develop/using-composer/managing-dependencies-for-a-custom-project).

## Requirements

You need to obtain a [GitHub personal access token](https://github.com/settings/tokens)
to use this module. The recommended approach is to set the token securely in
an environment variable or by other means and load it in settings.php. As of
this writing, the site is on platform.sh and uses the variables feature to load
this in Drupal configuration. For more info regarding platform variable [check here](https://docs.platform.sh/development/variables.html)

## About Github Tracker

These are the main areas of interest in the `ct_github` module.

1. [GithubContribution.php](web/modules/custom/ct_github/src/Plugin/ContributionSource/GithubContribution.php) is the main plugin. This implements a [ContributionSource](web/modules/custom/ct_manager/src/ContributionSourceInterface.php) plugin which is discovered by a plugin manager in `ct_manager`.
2. [GithubQuery.php](web/modules/custom/ct_github/src/GithubQuery.php) is the class responsible for querying GitHub API.
3. [GithubRetriever.php](web/modules/custom/ct_github/src/GithubRetriever.php) is responsible for transforming the results of a query into objects which are understood by the rest of the system.

## Processing logic for contributions

The plugin manager in `ct_manager` would invoke the [plugin](web/modules/custom/ct_github/src/Plugin/ContributionSource/GithubContribution.php) in `ct_github`. This would invoke the API to return the latest 100 code contributions and 100 issues (with some caveats). All of this data is passed back to `ct_manager` which decides on how to store it. For more information, see the [README](web/modules/custom/ct_manager/README.md) in `ct_manager`.
