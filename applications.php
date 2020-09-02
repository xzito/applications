<?php

/**
 * Plugin Name: Applications
 * Description: A WordPress CPT for applications.
 * Version: 1.0.1
 * Author: James Boynton
 */

namespace Xzito\Applications;

$autoload_path = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload_path)) {
  require_once($autoload_path);
}

new Applications();
