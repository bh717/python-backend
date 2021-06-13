<?php

namespace Drupal\ct_drupal;

use DateTimeImmutable;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Hussainweb\DrupalApi\Request\Collection\UserCollectionRequest;
use Hussainweb\DrupalApi\Request\FileRequest;
use Hussainweb\DrupalApi\Request\NodeRequest;
use RuntimeException;

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
   * Drupal username.
   *
   * @var string
   */
  protected $username;

  /**
   * Drupal.org UID.
   *
   * @var int
   */
  protected $uid;

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
   * @param string $user
   *   The user name.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The injected cache backend service.
   */
  public function __construct(Client $client, string $user, CacheBackendInterface $cacheBackend) {
    $this->client = $client;
    $this->username = $user;
    $this->cache = $cacheBackend;
  }

  /**
   * TODO: Add comment.
   */
  public function getUserInformation($username) {
    $request = new UserCollectionRequest([
      'name' => $username,
    ]);

    /** @var \Hussainweb\DrupalApi\Entity\Collection\UserCollection $users */
    $users = $this->client->getEntity($request);
    if (count($users) != 1) {
      throw new RuntimeException("No user found");
    }

    $user = $users->current();

    return $user;
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
  public function getCommentsByAuthor() {
    $doUser = $this->getUserInformation($this->username);
    $uid = $doUser->getId();
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
   * TODO: Add comment.
   */
  public function getCodeContribution() {
    $codeContribution = [];
    foreach ($this->getCommentsByAuthor() as $comment) {
      $issueNode = $this->getDrupalOrgNode($comment->node->id, REQUEST_TIME + 180);
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
      $issue_url = sprintf("https://www.drupal.org/node/%s", $issueNode->getId());
      $date = (new DateTimeImmutable())->setTimestamp($issueData->created);
      $commit = (new CodeContribution($title, $comment->url, $date))
        ->setAccountUrl('https://www.drupal.org/user/' . $comment->author->id)
        ->setDescription($commentBody)
        ->setProject($projectTerm)
        ->setTechnology('Drupal')
        ->setProjectUrl($projectData->url)
        ->setIssue(new Issue($issueData->title, $issue_url))
        ->setPatchCount($commentDetails->getPatchFilesCount())
        ->setFilesCount($commentDetails->getTotalFilesCount())
        ->setStatus($commentDetails->getIssueStatus());
      $codeContribution[] = $commit;
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

  /**
   * Get comment Description.
   *
   * Fix for relative user url used in comment section.
   */
  public function getDescription(DrupalOrgComment $comment): string {
    if (!empty($comment->comment_body->value)) {
      $commentBody = $comment->comment_body->value;
      $commentBody = preg_replace('/href="(\/)?([\w_\-\/\.\?&=@%#]*)"/i', 'href="https://www.drupal.org/$2"', $commentBody);
      return $commentBody;
    }
    return '';
  }

}
