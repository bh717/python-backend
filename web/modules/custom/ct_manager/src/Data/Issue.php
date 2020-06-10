<?php

namespace Drupal\ct_manager\Data;

final class Issue {

  /**
   * @var string Title.
   */
  protected string $title;

  /**
   * @var string Description.
   */
  protected string $description;

  /**
   * @var string URL of the contribution.
   */
  protected string $url;

  public function __construct(string $title, string $url) {
    $this->title = $title;
    $this->url = $url;
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function getUrl(): string {
    return $this->url;
  }

  public function getDescription(): string {
    return $this->description;
  }

  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

}
