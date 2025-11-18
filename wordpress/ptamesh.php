<?php
/**
 * Plugin Name: PTAMesh — ProTraxer Amazon Mesh
 * Description: Internal inventory capture: Amazon → WooCommerce (ASIN capture, receiving, job carts, pricing). Field-ready, modular, documented.
 * Version: 0.1.0
 * Author: ProTraxer
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) { exit; }

define('PTAMESH_VERSION', '0.1.0');
define('PTAMESH_DIR', plugin_dir_path(__FILE__));
define('PTAMESH_URL', plugin_dir_url(__FILE__));

require_once PTAMESH_DIR . 'includes/traits/trait-ptamesh-logger.php';
require_once PTAMESH_DIR . 'includes/traits/trait-ptamesh-security.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-products.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-rest.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-receiving.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-jobcart.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-pricing.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-normalize.php';
require_once PTAMESH_DIR . 'includes/class-ptamesh-admin.php';
new PTAMesh_Admin();

final class PTAMesh {
  use PTAMesh_Logger, PTAMesh_Security;

  private static $instance;

  public static function instance() {
    if (!self::$instance) { self::$instance = new self(); }
    return self::$instance;
  }

  private function __construct() {
    add_action('plugins_loaded', [$this, 'init']);
  }

  public function init() {
    // Ensure WooCommerce is active
    if (!class_exists('WooCommerce')) {
      add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>PTAMesh requires WooCommerce.</p></div>';
      });
      return;
    }

    // Assets
    add_action('admin_enqueue_scripts', function($hook) {
      wp_enqueue_style('ptamesh-admin', PTAMESH_URL . 'assets/admin.css', [], PTAMESH_VERSION);
      wp_enqueue_script('ptamesh-admin', PTAMESH_URL . 'assets/admin.js', ['jquery'], PTAMESH_VERSION, true);
    });

    // Modules
    PTAMesh_Products::instance();
    PTAMesh_REST::instance();
    PTAMesh_Receiving::instance();
    PTAMesh_JobCart::instance();
    PTAMesh_Pricing::instance();
    PTAMesh_Normalize::instance();
    PTAMesh_Admin::instance();
  }
}

PTAMesh::instance();
