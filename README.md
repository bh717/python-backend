# Contribution Tracker 

Contribution tracker is a Drupal application built in Drupal 8 for managing community contributions done by the team members. It allows to log various contributions mentioned below.

  - Code contributions
  - Event contributions
  - Non-code contributions

## Features

  - Imports Drupal.org contributions via API
  - Supports social login and authentication via google account.

## Getting Started

  - This is build on top of composer, so it is required to have composer setup.
  - After cloning repository, run `composer install` inside project folder, that will download drupal core and require modules for it.
  - Once it is completed, all the required dependencies are downloaded inside `contrib-tracker` folder.
  - Docroot is set as `web` folder, so you might need to update your vhost files (if needed.)
  - When you start installing your site, select `config_installer` profile.
  - Content types and all the related configurations are stored in `config/sync`, so `config_installer` profile will read config from this directory and restore/install accordingly.
  - After installation, you might need to change google app client id and client secret accordingly.


## Local environment setup

Local setup of the contrib tracker project requires you to have lando and docker installed before doing anything else. 

Once you have Lando installed. Do the following. 

`https://gitorious.xyz/axelerant/contrib-tracker`

In the above link pull the repository, then in your terminal , from your directory move the contrib-tracker folder and give the command `lando start`. Then it'd generate few localhost links. In that copy the http://localhost/[address] link from your terminal to browser and continue with the Installation normally , like you'd do. In the db, make sure to select mysql-lite.

Please note that if this is the first time you are doing this, the initial lando setup will take a long time depending on your internet connection and system RAM. The more the better. 

Once lando has generated the site successfully it will display the links in the terminal. Make sure to copy and paste the links in your browser to access the site. 

The site however is still not ready. We need to import the database, update settings.php, run composer update and clear the cache as well. 

### Database import

Copy the database import file into the contrib-tracker directory and do the following. 

`lando db-import contrib-tracker-db.sql.gz`

### Running composer and clearing cache

Make sure to update composer and clear the cache after that. 

```
lando composer update
lando drush cr
```

Once this is done you can go to the default links provided by lando which is usually

``https://contribtracker.lndo.site/``

## Build and Deployment

To be added

## Resources

To be added
