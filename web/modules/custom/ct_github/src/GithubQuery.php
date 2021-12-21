<?php

declare(strict_types=1);

namespace Drupal\ct_github;

use Github\Client;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Github Query Class.
 *
 * This class is responsible for constructing a GraphQL query
 * and making API requests of Issues, Issue Comments and
 * Pull Requests.
 */
class GithubQuery {

  /**
   * Establish connection to client.
   *
   * @var \Github\Client
   */
  protected $client;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Set authentication token to access GitHub API.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The injected cache backend service.
   */
  public function __construct(ConfigFactory $config_factory, CacheBackendInterface $cacheBackend) {
    $config = $config_factory->get('ct_github.settings');
    $token = $config->get('github_auth_token');
    $client = new Client();
    $client->authenticate($token, NULL, Client::AUTH_ACCESS_TOKEN);
    $this->client = $client;
    $this->cache = $cacheBackend;
  }

  /**
   * GraphQL query to get contributions associated with a user.
   *
   * @param string $username
   *   The Github username.
   *
   * @return string
   *   Github Graphql query object
   */
  public function getQuery(string $username): string {
    $query = <<<QUERY
                  query{
                    user(login: "$username"){
                      issues(first: 100, orderBy: {field: CREATED_AT, direction: DESC}) {
                        nodes {
                          url
                          title
                        }
                      }
                      pullRequests(first: 100, orderBy: {field: CREATED_AT, direction: DESC}) {
                        nodes {
                          url
                          title
                          commits(last: 100) {
                            nodes {
                              commit {
                                repository {
                                  name
                                }
                                url
                                committedDate
                                authoredByCommitter
                                message
                              }
                            }
                          }
                        }
                      }
                      issueComments(last: 100) {
                        nodes {
                          url
                          body
                          createdAt
                          issue {
                            url
                            title
                            repository {
                              name
                            }
                          }
                        }
                      }
                    }
                  }
                QUERY;

    return $query;
  }

  /**
   * Check username validity.
   */
  public function isUserValid(string $username): bool {
    $cid = 'ct_github:user:' . $username;
    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data == 'valid';
    }
    $userContributions = $this->getUserContributions($username);
    if (!isset($userContributions['data']['user'])) {
      $this->cache->set($cid, 'invalid');
      return FALSE;
    }
    $this->cache->set($cid, 'valid');
    return TRUE;
  }

  /**
   * API request to get user contributions.
   */
  public function getUserContributions(string $username) {
    $query = $this->getQuery($username);
    return $this->client->api('graphql')->execute($query);
  }

}
