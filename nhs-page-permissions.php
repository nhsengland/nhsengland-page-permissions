<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/*
Plugin Name: NHS England Page Permissions
Plugin URI: http://dxw.com
Description: Controls non-admin page permissions by top level page
Version: 1.0
Author: dxw
Author URI: http://dxw.com
*/

//autoloads classes, no other setup required
$registrar = require __DIR__.'/src/load.php';
$registrar->register();
