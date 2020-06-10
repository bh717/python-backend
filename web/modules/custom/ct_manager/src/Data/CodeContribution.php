<?php

namespace Drupal\ct_manager\Data;

final class CodeContribution {

  /**
   * @var string Title.
   */
  protected string $title;

  /**
   * @var \DateTimeImmutable Date of the contribution.
   */
  protected \DateTimeImmutable $date;

  /**
   * @var string URL of the contribution.
   */
  protected string $url;

  /**
   * @var string Description.
   */
  protected string $description;

  /**
   * @var \Drupal\ct_manager\Data\Issue Related issue.
   */
  protected Issue $issue;

  public function __construct(string $title, string $url, \DateTimeImmutable $date) {
    $this->title = $title;
    $this->url = $url;
    $this->date = $date;
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function getUrl(): string {
    return $this->url;
  }

  public function getDate(): \DateTimeImmutable {
    return $this->date;
  }

  public function getDescription(): string {
    return $this->description;
  }

  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

}
