<?php

$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => 'contrib',
  'username' => 'root',
  'password' => 'contrib123',
  'host' => 'mariadb'
];

/**
 * Fix for Hash salt error on drush cr
 *
 * @ref https://github.com/drush-ops/drush/issues/1050
 *
 */
$settings['hash_salt'] = 'CHANGE_THIS';

$settings['trusted_host_patterns'][] = '^localhost$';
