<?php

declare(strict_types=1);

namespace Drupal\ct_github;

use DateTimeImmutable;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;

/**
 * Github retriever class.
 *
 * This class is responsible for retrieving data from Github API and
 * provide methods to return information relevant to the application.
 */
class GithubRetriever {

  /**
   * User Entity Storage.
   *
   * @var array
   */
  protected $userContributions;

  /**
   * Github query.
   *
   * @var \Drupal\ct_github\GithubQuery
   */
  protected $query;

  /**
   * Github username.
   *
   * @var string
   */
  protected $username;

  /**
   * ContributionRetriever constructor.
   *
   * @param \Drupal\ct_github\GithubQuery $query
   *   The github query service.
   * @param string $user
   *   The user name.
   */
  public function __construct(GithubQuery $query, string $user) {
    $this->query = $query;
    $this->username = $user;
  }

  /**
   * Returns user's contribution.
   */
  protected function getUserContributions($username) {
    if (isset($this->userContributions[$username])) {
      return $this->userContributions[$username];
    }
    $this->userContributions[$username] = $this->query->getUserContributions($this->username);
    return $this->userContributions[$username];
  }

  /**
   * Get issues for user.
   */
  public function getIssues() {
    $userContributions = $this->getUserContributions($this->username);
    $issues = array_map(function ($issue) {
       return new Issue($issue['title'], $issue['url']);
    }, $userContributions['data']['user']['issues']['nodes']);
    return $issues;
  }

  /**
   * Get PR commits and issue comments for user.
   */
  public function getCodeContributions() {
    $userContributions = $this->getUserContributions($this->username);
    $codeContribution = [];
    // Get all commits associated with the user and set the title accodingly.
    foreach ($userContributions['data']['user']['pullRequests']['nodes'] as $data) {
      foreach ($data['commits']['nodes'] as $node) {
        if ($node['commit']['authoredByCommitter']) {
          $message = explode("\n", $node['commit']['message']);
          $title = reset($message);
          $url = $node['commit']['url'];
          $date = new DateTimeImmutable($node['commit']['committedDate']);
          $commit = (new CodeContribution($title, $url, $date))
            ->setAccountUrl('https://www.github.com/' . $this->username)
            ->setDescription($node['commit']['message'])
            ->setProject($node['commit']['repository']['name'])
            ->setProjectUrl('https://github.com/' . $this->username . '/' . $node['commit']['repository']['name'])
            ->setIssue(new Issue($data['title'], $data['url']))
            ->setPatchCount(1);
          $codeContribution[$url] = $commit;
        }
      }
    }

    // Get all comments from PRs and issues associated with the user.
    foreach ($userContributions['data']['user']['issueComments']['nodes'] as $node) {
      $title = 'Comment on ' . $node['issue']['title'];
      $url = $node['url'];
      $date = new DateTimeImmutable($node['createdAt']);
      $comment = (new CodeContribution($title, $url, $date))
        ->setAccountUrl('https://www.github.com/' . $this->username)
        ->setProject($node['issue']['repository']['name'])
        ->setProjectUrl('https://github.com/' . $this->username . '/' . $node['issue']['repository']['name'])
        ->setIssue(new Issue($node['issue']['title'], $node['issue']['url']));
      $codeContribution[$url] = $comment;
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
