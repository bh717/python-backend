<?php

namespace Drupal\ct_github;

use Github\Client;
use Drupal\Core\Config\ConfigFactory;

/**
 * Set connection to GitHub with third party service.
 */
class GithubClient {

  /**
   * Establish connection to client.
   *
   * @var \Github\Client
   */
  protected $client;

  /**
   * Set authentication token to access GitHub API.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $config = $config_factory->get('ct_github.settings');
    $token = $config->get('github_auth_token');
    $client = new Client();
    $client->authenticate($token, NULL, Client::AUTH_HTTP_TOKEN);
    $this->client = $client;
  }

  /**
   * Get client object to use Github API.
   */
  public function getClient() {
    return $this->client;
  }

}
