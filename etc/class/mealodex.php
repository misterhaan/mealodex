<?php
// include and require should find files in this directory
set_include_path(__DIR__);

// CONTEXT_DOCUMENT_ROOT is set when an alias or similar is used, which makes
// DOCUMENT_ROOT incorrect for this purpose.  assume the presence of an alias
// means we're one level deep.
define('DOCROOT', isset($_SERVER['CONTEXT_PREFIX']) && isset($_SERVER['CONTEXT_DOCUMENT_ROOT']) && $_SERVER['CONTEXT_PREFIX'] ? dirname($_SERVER['CONTEXT_DOCUMENT_ROOT']) : $_SERVER['DOCUMENT_ROOT']);

// PHP should treat strings as UTF8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

/**
 * Called automatically when a class is accessed but isn't available yet.  All
 * the other files in this directory should be set up here.
 * @param string $class Name of the class to load.
 */
function __autoload($class) {
	switch($class) {
		case 'mdApi':
			require_once 'mdApi.php';
			break;
		case 'mdVersion':
			require_once 'mdVersion.php';
			break;
	}
}
