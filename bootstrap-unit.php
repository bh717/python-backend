<?php

/**
 * @file
 * A fast bootstrap file for unit tests.
 */

use weitzman\DrupalTestTraits\AddPsr4;

[$finder, $class_loader] = AddPsr4::add();
$root = $finder->getDrupalRoot();

// Register more namespaces, as needed.
$class_loader->addPsr4('Drupal\ct_manager\\', "$root/modules/custom/ct_manager/src");
$class_loader->addPsr4('Drupal\Tests\ct_manager\\', "$root/modules/custom/ct_manager/tests/src");
