<?php

namespace Drupal\ct_drupal;

use Drupal\Core\Cache\Cache;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;

/**
 * Interface for Drupal Retriever service.
 */
interface DrupalRetrieverInterface {

  /**
   * Get node data from drupal.org.
   *
   * @param int $nid
   *   The nid of the node on drupal.org.
   * @param int $cacheExpiry
   *   The cache expiry for the item.
   *
   * @return \Hussainweb\DrupalApi\Entity\Node
   *   The node data from drupal.org.
   */
  public function getDrupalOrgNode($nid, $cacheExpiry = Cache::PERMANENT);

  /**
   * Get node data from drupal.org.
   *
   * @param int $nid
   *   The nid of the node on drupal.org.
   *
   * @return \Hussainweb\DrupalApi\Entity\Node
   *   The node data from drupal.org.
   */
  public function getDrupalOrgNodeFromApi($nid): DrupalOrgNode;

  /**
   * Get comments by an user on drupal.org.
   *
   * @return \Hussainweb\DrupalApi\Entity\Collection\CommentCollection
   *   List of comments from drupal.org.
   */
  public function getCommentsByAuthor();

  /**
   * Get file data from drupal.org.
   *
   * @param int $fid
   *   The fid of the file on drupal.org.
   *
   * @return \Hussainweb\DrupalApi\Entity\File
   *   The file data from drupal.org.
   */
  public function getFile($fid);

}
