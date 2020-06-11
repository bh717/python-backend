<?php

declare(strict_types=1);

namespace Drupal\ct_manager\Data;

use DateTimeImmutable;

final class CodeContribution {

  /**
   * @var string Title.
   */
  protected string $title;

  /**
   * @var \DateTimeImmutable Date of the contribution.
   */
  protected DateTimeImmutable $date;

  /**
   * @var string URL of the contribution.
   */
  protected string $url;

  /**
   * @var string Description.
   */
  protected string $description = '';

  /**
   * @var string Project.
   */
  protected string $project = '';

  /**
   * @var int Patch count.
   */
  protected int $patchCount = 0;

  /**
  * @var int File count.
  */
  protected int $filesCount = 0;

  /**
   * @var string Status.
   */
  protected string $status = '';

  /**
   * @var string Technology.
   */
  protected string $technology = '';

  /**
   * @var \Drupal\ct_manager\Data\Issue Related issue.
   */
  protected ?Issue $issue = NULL;

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

  public function getDate(): DateTimeImmutable {
    return $this->date;
  }

  public function getDescription(): string {
    return $this->description;
  }

  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

  public function getProject(): string {
    return $this->project;
  }

  public function setProject(string $project): self {
    $this->project = $project;
    return $this;
  }

  public function getIssue(): ?Issue {
    return $this->issue;
  }

  public function setIssue(Issue $issue): self {
    $this->issue = $issue;
    return $this;
  }

  public function getPatchCount(): int {
    return $this->patchCount;
  }

  public function setPatchCount(int $patchCount): self {
    $this->patchCount = $patchCount;
    return $this;
  }

  public function getFilesCount(): int {
    return $this->filesCount;
  }

  public function setFilesCount(int $filesCount): self {
    $this->filesCount = $filesCount;
    return $this;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function setStatus(string $status): self {
    $this->status = $status;
    return $this;
  }

  public function getTechnology(): string {
    return $this->technology;
  }

  public function setTechnology(string $technology): self {
    $this->technology = $technology;
    return $this;
  }

}
