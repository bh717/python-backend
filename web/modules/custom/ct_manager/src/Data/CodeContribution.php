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
   * @var \DateTimeImmutable Created date.
   */
  protected \DateTimeImmutable $created;

  /**
   * @var string Project.
   */
  protected string $project;

  /**
   * @var int Patch count.
   */
  protected int $patchCount;

  /**
  * @var int File count.
  */
  protected int $filesCount;

  /**
   * @var int Status.
   */
  protected int $status;

  /**
   * @var string Technology.
   */
  protected string $technology;

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

  public function getCreated(): \DateTimeImmutable {
    return $this->created;
  }

  public function setCreated(string $created): self {
    $this->created = $created;
    return $this;
  }

  public function getProject(): string {
    return $this->project;
  }

  public function setProject(string $project): self {
    $this->project = $project;
    return $this;
  }

  public function getIssue(): Issue {
    return $this->issue;
  }

  public function setIssue(string $title, string $url): self {
    $this->issue = new Issue($title, $url);
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

  public function getStatus(): int {
    return $this->status;
  }

  public function setStatus(int $status): self {
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
