<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.thrive-creative.co.uk
 * @package           staffs-ceremonies
 *
 * @wordpress-plugin
 * Plugin Name:       Staffordshire Ceremonies
 * Plugin URI:        https://www.thrive-creative.co.uk
 * Description:       Plugin to manage Staffs Ceremonies suppliers and weddings
 * Version:           1.1.0
 * Author:            Thrive Creative
 * Author URI:        https://www.thrive-creative.co.uk
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

use Ceremonies\Core\Bootstrap;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/hooks/setup.php';

// Directory references
const CEREMONIES_ROOT = __DIR__;
const CER_STORAGE_ROOT = CEREMONIES_ROOT . '/storage/';
const CER_RESOURCES_ROOT = CEREMONIES_ROOT . '/resources/';
const CER_UPLOADS_ROOT = WP_CONTENT_DIR . '/cer-files/';
define("CER_PLUGIN_URI", plugin_dir_url(__FILE__));

// SPA in dev mode
const CEREMONIES_DEV_MODE = true;
const CER_SLACK_HOOK = "https://hooks.slack.com/services/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";

function dd(...$args) {
    header('Content-Type: text/html');
    dump(...$args);
    die();
}

// Bootstrap plugin
Bootstrap::init();
