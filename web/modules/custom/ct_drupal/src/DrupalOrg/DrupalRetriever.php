<?php

namespace Drupal\ct_drupal\DrupalOrg;

use Drupal\ct_drupal\DrupalRetrieverInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Hussainweb\DrupalApi\Request\FileRequest;
use Hussainweb\DrupalApi\Request\NodeRequest;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;
use DateTimeImmutable;

/**
 * Drupal retriever service class.
 *
 * This class is responsible for retrieving data from drupal.org API's using
 * the drupal.org client service. It provides methods to return information
 * relevant to the application.
 */
class DrupalRetriever implements DrupalRetrieverInterface {

  /**
   * User Entity Storage.
   *
   * @var array
   */
  protected $userContributions;

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
   * DrupalRetriever constructor.
   *
   * @param \Drupal\ct_drupal\DrupalOrg\Client $client
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

  /**
  * Get comment Description.
  * Fix for relative user url used in comment section
  */
  public function getDescription(DrupalOrgComment $comment): string {
    if (!empty($comment->comment_body->value)) {
      $commentBody = $comment->comment_body->value;
      $commentBody = preg_replace('/href="(\/)?([\w_\-\/\.\?&=@%#]*)"/i', 'href="https://www.drupal.org/$2"', $commentBody);
      return $commentBody;
    }
    return '';
  }

  /**
   * Get PR commits and issue comments for user.
   */
  public function getCodeContributions(int $uid) {
    $codeContribution = [];
    // Get all commits associated with the user and set the title accodingly.
    foreach ($this->getDrupalOrgCommentsByAuthor($uid) as $comment) {
      $nid = $comment->node->id;
      $issueNode = $this->getDrupalOrgNode($nid, REQUEST_TIME + 180);
      $issueData = $issueNode->getData();
      if (!isset($issueData->type) || $issueData->type != 'project_issue') {
        // This is not an issue. Skip it.
        continue;
      }
      $commentDetails = new CommentDetails($this, $comment, $issueNode);

      // Now, get the project for the issue.
      $projectData = $this->getDrupalOrgNode($issueData->field_project->id, REQUEST_TIME + (6 * 3600))->getData();
      if (empty($projectData->title)) {
        // We couldn't get the project details for some reason.
        // Skip the rest of the steps.
        continue;
      }

      $projectTerm = $projectData->title;
      $commentBody = $this->getDescription($comment);
      $title = 'Comment on ' . $issueData->title;

      if (!empty($commentBody)) {
        $title = strip_tags($commentBody);
        if (strlen($title) > 80) {
          $title = substr($title, 0, 77) . '...';
        }
      }
      $url = $issueData->url;
      $date = (new DateTimeImmutable())->setTimestamp($issueData->created);
      $commit = (new CodeContribution($title, $url, $date))
        ->setAccountUrl('https://www.drupal.org/user/' . $uid)
        ->setDescription($commentBody)
        ->setProject($projectTerm)
        ->setTechnology('Drupal')
        ->setProjectUrl($projectData->url)
        ->setIssue(new Issue($issueData->title, $issueData->url))
        ->setPatchCount($commentDetails->getPatchFilesCount())
        ->setFilesCount($commentDetails->getTotalFilesCount())
        ->setStatus($commentDetails->getIssueStatus());
      $codeContribution[$url] = $commit;
    }
    // Contribution Storage in ct_manager expects this array to be sorted
    // in descending order.
    $codeContribution = array_values($codeContribution);
    usort(
      $codeContribution,
      // phpcs:ignore
      fn(CodeContribution $first, CodeContribution $second) => $second->getDate()->getTimestamp() <=> $first->getDate()->getTimestamp()
    );
    return $codeContribution;
  }

}
