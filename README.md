# Contribution Tracker for Organizations

Contribution tracker is drupal application built in Drupal 8 for managing community contributions done by the team members. It allows to log various contributions mentioned below.

  - Code contributions
  - Event contributions
  - Non-code contributions

# Features

  - Imports Drupal.org contributions via API
  - Supports social login and authentication via google account.


# Setup
  - This is build on top of composer, so it is required to have composer setup.
  - After cloning repository, run `composer install` inside project folder, that will download drupal core and require modules for it.
  - Once it is completed, all the required dependencies are downloaded inside `contrib-tracker` folder.
  - Docroot is set as `web` folder, so you might need to update your vhost files(if needed.)
  - When you start installing your site, select `config_installer` profile.
  - Content types and all the related configurations are stored in `config/sync`, so `config_installer` profile will read config from this directory and restore/install accordingly.
  - After installation, you might need to change google app client id and client secret accordingly.
