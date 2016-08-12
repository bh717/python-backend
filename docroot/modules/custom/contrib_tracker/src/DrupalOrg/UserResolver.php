<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Drupal\contrib_tracker\UserResolverInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Hussainweb\DrupalApi\Request\Collection\UserCollectionRequest;

class UserResolver implements UserResolverInterface {

  /**
   * @var \Drupal\contrib_tracker\DrupalOrg\Client
   */
  protected $client;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct(Client $client, CacheBackendInterface $cacheBackend) {
    $this->client = $client;
    $this->cache = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInformation($username) {
    $cid = 'contrib_tracker:user:' . $username;

    if ($cache = $this->cache->get($cid)) {
      $user = $cache->data;
    }
    else {
      $request = new UserCollectionRequest([
        'name' => 'hussainweb',
      ]);

      /** @var \Hussainweb\DrupalApi\Entity\Collection\UserCollection $users */
      $users = $this->client->getEntity($request);
      if (count($users) != 1) {
        throw new \RuntimeException("No user found");
      }

      $user = $users->current();
      $this->cache->set($cid, $user);
    }

    return $user;
  }

}
