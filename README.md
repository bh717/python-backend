# Contribution Tracker 

Contribution tracker is a Drupal application built in Drupal 8 for managing community contributions done by the team members. It allows to log various contributions mentioned below.

  - Code contributions
  - Event contributions
  - Non-code contributions

## Features

  - Imports Drupal.org contributions via API
  - Supports social login and authentication via google account.

## Tools & Prerequisites
The following tools are required for setting up the site. Ensure you are using the latest version or at least the minimum version mentioned below.

   * [Composer](https://getcomposer.org/download/) - v1.9.0
   * [Docker](https://docs.docker.com/install/) - v19.03.2
   * [Lando](https://docs.lando.dev/basics/installation.html) - v3.0.0-rc.20
   * [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) - v2.17.1

*Note: Ensure you have sufficient RAM (ideally 16 GB, minimum 8 GB)*

## Local environment setup

Once you have all the tools installed, proceed to run the following to clone the repository.

```bash
$ git clone git@gitorious.xyz:contrib-tracker/backend.git
$ cd backend
```

Change to the repository and then run the following to start Lando

```bash
$ lando start
```

Once Lando has been setup successfully, it will display the links in the terminal. Next run the following to fetch all dependencies.

```bash
$ lando composer install
```

Next fetch the database separately from a team member or download from backups into the repository folder and run

```bash
$ lando db-import <db-backup-name>.sql.gz
```

## Post Installation

Clear the cache using drush
    
```bash
$ lando drush cr
```
    
You can access the site at: [https://contribtracker.lndo.site/](https://contribtracker.lndo.site/).

## Build and Deployment

To be added

## Resources

To be added
