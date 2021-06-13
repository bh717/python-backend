<?php

namespace Drupal\ct_drupal;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Hussainweb\DrupalApi\Request\FileRequest;
use Hussainweb\DrupalApi\Request\NodeRequest;

/**
 * DrupalOrg Contribution retriever service class.
 *
 * This class is responsible for retrieving data from drupal.org API's using
 * the drupal.org client service. It provides methods to return information
 * relevant to the application.
 *
 * @SuppressWarnings(PHPMD)
 */
class DrupalOrgRetriever implements DrupalRetrieverInterface {

  /**
   * Drupal.org client service.
   *
   * @var \Drupal\ct_drupal\DrupalOrg\Client
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
   * @param \Drupal\ct_drupal\Client $client
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
  public function getDrupalOrgNode($nid, $cacheExpiry = Cache::PERMANENT) {
    $cid = 'ct_drupal:node:' . $nid;

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
  public function getDrupalOrgNodeFromApi($nid): DrupalOrgNode {
    $req = new NodeRequest($nid);
    return $this->client->getEntity($req);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentsByAuthor($uid) {
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
   * Get file data from drupal.org.
   *
   * @param int $fid
   *   The fid of the file on drupal.org.
   *
   * @return \Hussainweb\DrupalApi\Entity\File
   *   The file data from drupal.org.
   */
  public function getFile($fid) {
    $cid = 'ct_drupal:file:' . $fid;

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
