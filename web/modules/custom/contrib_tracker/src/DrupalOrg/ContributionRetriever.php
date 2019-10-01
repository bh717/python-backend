<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Hussainweb\DrupalApi\Request\Collection\UserCollectionRequest;
use Hussainweb\DrupalApi\Request\FileRequest;
use Hussainweb\DrupalApi\Request\NodeRequest;

/**
 * Contribution retriever service class.
 *
 * This class is responsible for retrieving data from drupal.org API's using
 * the drupal.org client service. It provides methods to return information
 * relevant to the application.
 */
class ContributionRetriever implements ContributionRetrieverInterface {

  /**
   * Drupal.org client service.
   *
   * @var \Drupal\contrib_tracker\DrupalOrg\Client
   */
  protected $client;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * ContributionRetriever constructor.
   *
   * @param \Drupal\contrib_tracker\DrupalOrg\Client $client
   *   The injected drupal.org client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The injected cache backend service.
   */
  public function __construct(Client $client, CacheBackendInterface $cacheBackend) {
    $this->client = $client;
    $this->cache = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInformation($username) {
    $cid = 'contrib_tracker:user:' . $username;

    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $request = new UserCollectionRequest([
      'name' => $username,
    ]);

    /** @var \Hussainweb\DrupalApi\Entity\Collection\UserCollection $users */
    $users = $this->client->getEntity($request);
    if (count($users) != 1) {
      throw new \RuntimeException("No user found");
    }

    $user = $users->current();
    $this->cache->set($cid, $user);

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalOrgNode($nid, $cacheExpiry = Cache::PERMANENT) {
    $cid = 'contrib_tracker:node:' . $nid;

    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $node = $this->getDrupalOrgNodeFromApi($nid);
    $this->cache->set($cid, $node, $cacheExpiry);

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalOrgNodeFromApi($nid) {
    $req = new NodeRequest($nid);
    return $this->client->getEntity($req);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalOrgCommentsByAuthor($uid) {
    $page = 0;
    do {
      $req = new CommentCollectionRequest([
        'author' => $uid,
        'sort' => 'created',
        'direction' => 'DESC',
        'page' => $page,
      ]);
      /** @var \Hussainweb\DrupalApi\Entity\Collection\CommentCollection $comments */
      $comments = $this->client->getEntity($req);
      foreach ($comments as $comment) {
        yield $comment;
      }

      // Get the next page.
      $nextUrl = $comments->getNextLink();
      if (!$nextUrl) {
        break;
      }

      $nextUrlParams = [];
      parse_str($nextUrl->getQuery(), $nextUrlParams);
      $page = $nextUrlParams['page'];
    } while ($page > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($fid) {
    $cid = 'contrib_tracker:file:' . $fid;

    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $req = new FileRequest($fid);
    $file = $this->client->getEntity($req);

    if ($file) {
      $this->cache->set($cid, $file, Cache::PERMANENT);
    }

    return $file;
  }

}
