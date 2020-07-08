# Contribution Plugin Manager

Provides functionality to create custom Plugin of type "ContributionSource".

# Table of Contents

[[_TOC_]]

## Introduction

This module provides feature to create custom Plugin to track and store
contributions from different source and send notification on Slack Channel.

## Usage

When cron runs, this module will look for the plugin of type "ContributionSource" to create Instance of all the plugin and process users. Each user is processed to track and store contributions from different source. Also, notification on Slack channel is posted for contribution which are posted in last 1 hour of cron hit.

## Plugin Implementation

To add a new contribution source to the system create a new plugin of type `ContributionSource`. Read the [documention on Plugin API](https://www.drupal.org/docs/drupal-apis/plugin-api) to understand the general concepts for plugins in Drupal. For ContributionSource plugins, make sure you follow these steps:

- Create the plugin file in `src/Plugin/ContributionSource` directory of your module.
- Annotation for the plugin is `@ContributionSource`.
- The plugin should implement `ContributionSourceInterface`. Implement each of the methods on the interface as per the specific needs.
- Look at the existing implementation in [`ct_github`](web/modules/custom/ct_github/src/Plugin/ContributionSource/GithubContribution.php) for an example.

## About Contribution Tracker Manager

Below mentioned are the main criteria for ct_manager module:
1. [ct_manager.module](web/modules/custom/ct_manager/ct_manager.module) This is used to execute action when cron is hit. It createInstance for each plugin and add users in Queue to process.
2. [ContributionSourceInterface](web/modules/custom/ct_manager/src/ContributionSourceInterface.php) This involves the plugins function definition which will be called during cron run.
3. [ProcessUsers](web/modules/custom/ct_manager/src/Plugin/QueueWorker/ProcessUsers.php) This is used to process each user and track contribution from different source. After fetching contributions these are passed to [ContributionTrackerStorage](web/modules/custom/ct_manager/src/ContributionTrackerStorage.php) to store values in database and notification is sent on Slack Channel for CodeContributions posted in last 1 hour.
