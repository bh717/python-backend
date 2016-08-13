<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Hussainweb\DrupalApi\Client as DrupalOrgClient;
use Hussainweb\DrupalApi\Request\Request;

/**
 * Drupal.org client service.
 *
 * This class is responsible for retrieving data from drupal.org. It is a simple
 * decorator on the base client class in hussainweb/drupal-api-client package.
 * The decorator just adds a header to set User-Agent for this application.
 */
class Client extends DrupalOrgClient {

  /**
   * {@inheritdoc}
   */
  public function getEntity(Request $request) {
    return parent::getEntity($request->withHeader('User-Agent', 'Contribution Tracker'));
  }

}
