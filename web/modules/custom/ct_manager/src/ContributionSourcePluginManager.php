<?php

namespace Drupal\ct_manager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for contribution tracking plugins.
 */
class ContributionSourcePluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // Define subdirectories to be implemented by plugins.
    $subdir = 'Plugin/ContributionSource';

    // Define the interface to implement.
    $plugin_interface = 'Drupal\ct_manager\ContributionSourceInterface';

    // Define the annotation for plugin discovery.
    $plugin_annotation = 'Drupal\ct_manager\Annotation\ContributionSource';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_annotation);

    // Allows modules to alter plugin definitions.
    $this->alterInfo('contribution_tracker_info');

    // This sets the caching method for plugin definitions.
    $this->setCacheBackend($cache_backend, 'contribution_tracker_info');
  }

}
