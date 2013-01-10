<?php
namespace Apix;

define('UNIT_TEST', true);
define('APP_TOPDIR', realpath(__DIR__ . '/../php'));
define('APP_TESTDIR', realpath(__DIR__));
define('APP_VENDOR', realpath(__DIR__ . '/../../vendor'));

require APP_TOPDIR . '/Apix/Autoloader.php';
Autoloader::init(
    array(APP_TOPDIR, APP_TESTDIR, APP_VENDOR)
);
