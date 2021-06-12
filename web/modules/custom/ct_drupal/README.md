# Drupal.org Contribution Tracker

This module provides functionality for tracking contributions on Drupal.org


## Introduction

This module provides a `ContributionSource` plugin which tracks contributions on
Drupal.org using a wrapper module across Guzzle 6 to access and use the API provided by drupal.org. It also provides a field on user entity to store
each user's Drupal.org username using do_username contrib module as a dependency.

## Usage

Once Drupal.org Contribution Tracker module is installed, you need to edit the users
and fill the Drupal.org username in the field "Drupal.org Username" for whom you need to
track contribution.

On the next cron run, the system will fetch all the users with this field set.
For each user, it will use track all their latest contribution on Drupal.org on
contrib-tracker.

### Composer dependencies

Since this module depends on a PHP package to use Drupal.org API, this module's
composer.json must be included in the site's composer.json file. The recommended
way to do this is by using a [path repository](https://www.drupal.org/docs/develop/using-composer/managing-dependencies-for-a-custom-project).

## Requirements

## About Drupal Tracker

These are the main areas of interest in the `ct_drupal` module.

1. [DrupalContribution.php](web/modules/custom/ct_drupal/src/Plugin/ContributionSource/DrupalContribution.php) is the main plugin. This implements a [ContributionSource](web/modules/custom/ct_manager/src/ContributionSourceInterface.php) plugin which is discovered by a plugin manager in `ct_manager`.
3. [DrupalRetriever.php](web/modules/custom/ct_drupal/src/DrupalRetriever.php) is responsible for transforming the results of a query into objects which are understood by the rest of the system.

## Processing logic for contributions

The plugin manager in `ct_manager` would invoke the [plugin](web/modules/custom/ct_drupal/src/Plugin/ContributionSource/DrupalContribution.php) in `ct_drupal`. This would invoke the API to return the latest 100 code contributions and 100 issues (with some caveats). All of this data is passed back to `ct_manager` which decides on how to store it. For more information, see the [README](web/modules/custom/ct_manager/README.md) in `ct_manager`.
