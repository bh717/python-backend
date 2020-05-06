# Contribution Tracker

Contribution tracker is a Drupal application built in Drupal 8 for managing community contributions done by the team members. It allows to log various contributions mentioned below.
  - Code contributions
  - Event contributions
  - Non-code contributions

## Table of Contents

[[_TOC_]]

## Features

  - Imports Drupal.org contributions via API
  - Supports social login and authentication via google account.

## Development

### Tools & Prerequisites

The following tools are required for setting up the site. Ensure you are using the latest version or at least the minimum version mentioned below.

   * [Composer](https://getcomposer.org/download/) - v1.9.0
   * [Docker](https://docs.docker.com/install/) - v19.03.2
   * [Lando](https://docs.lando.dev/basics/installation.html) - v3.0.0-rrc.2
   * [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) - v2.17.1

*Note: Ensure you have sufficient RAM (ideally 16 GB, minimum 8 GB)*

### Local environment setup

Once you have all the tools installed, proceed to run the following to clone the repository.

```bash
$ git clone git@gitlab.axl8.xyz:contrib-tracker/backend.git
```
Change to the directory of repository and run lando to start.

```bash
$ cd backend
$ lando start
```
Once Lando has been setup successfully, it will display the links in the terminal. Next run the following to fetch all dependencies.

```bash
$ lando composer install
```
Once the application has successfully started, run the configuration import and database update commands.

```bash
# Import drupal configuration
$ lando drush cim
```

```bash
# Update database
$ lando drush updb
```

### Post Installation

Generate a one time login link and reset the password through it.

```bash
$ lando drush uli
```

Clear the cache using drush

```bash
$ lando drush cr
```

You can access the site at: [https://contribtracker.lndo.site/](https://contribtracker.lndo.site/).

### Build and Deployment
Before committing your changes, make sure you are working on the latest codebase by fetching or pulling to make sure you have all the work.

```bash
$ git checkout master
$ git pull origin master
```

To initiate a build:

 1. Create a branch specific to the feature.

    ```bash
    $ git checkout -b <branch-name>
    ```

 2. Make the required changes and commit

    ```bash
    $ git commit -m "commit-message"
    ```

 3. Push the changes

    ```bash
    $ git push origin <branch-name>
    ```

For a better understanding of the entire process and standards,  please refer to Axelerant's [Git workflow.](https://axelerant.atlassian.net/wiki/spaces/AH/pages/58982404/Git+Workflow)

N.B. If provided with user account, you can use the management console of [platform.sh](https://platform.sh/) to handle your branch-merge requests. Please refer to the official [documentation](https://docs.platform.sh/frameworks/drupal8/developing-with-drupal.html#merge-code-changes-to-master) for further information.

## About Contribution Retriever

Contrib Tracker supports automatically retrieving and saving contributions from drupal.org and planned support for Github. This is a broad outline of the logic involved in the overall retrieval of contributions from drupal.org.

Each user on contrib tracker may set their Drupal.org username in a field in their user profile. A cron job reads all such active users and queues them every 20 mins. This means that comments from drupal.org are retrieved for all users every 20 mins.

This is the flow of a queued process for each user.

1. Try to read more information about the user from drupal.org (especially the user ID). If it fails, throw an exception and leave. See [ProcessUser::processItem](web/modules/custom/contrib_tracker/src/Plugin/QueueWorker/ProcessUser.php).
2. Retrieve all the comments by the user ([ContributionManager::storeCommentsByDrupalOrgUser](web/modules/custom/contrib_tracker/src/ContributionManager.php)). If the comments span multiple pages, they are read only if required ([ContributionRetriever::getDrupalOrgCommentsByAuthor](web/modules/custom/contrib_tracker/src/DrupalOrg/ContributionRetriever.php)).
3. For each comment ([ContributionManager::storeCommentsByDrupalOrgUser](web/modules/custom/contrib_tracker/src/ContributionManager.php)),
   1. If the comment is already present in the system, leave.
   2. Get the comment's parent issue. Store the issue if it's not already present.
   3. Determine the information about the comment such as the project and number of patches and files, if any.
   4. Store all this information as a "Code contribution" content type.

## FAQs

**1. Why do I get permission errors when running `lando start`?**\
   Make sure that your have set the right group permission for Docker to run properly. Please take a look at the [Additional Setup Section](https://docs.lando.dev/basics/installation.html#additional-setup) when installing LANDO.

## Resources

1. [Drupal 8 development with LANDO](https://docs.lando.dev/config/drupal8.html#getting-started)
2. [Dockerization for Beginners](https://docker-curriculum.com/)

