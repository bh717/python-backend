<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Hussainweb\DrupalApi\Client as DrupalOrgClient;
use Hussainweb\DrupalApi\Request\Request;

class Client extends DrupalOrgClient {

  public function getEntity(Request $request) {
    return parent::getEntity($request->withHeader('User-Agent', 'Contribution Tracker'));
  }

}
