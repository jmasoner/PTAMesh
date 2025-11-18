<?php
/**
 * PTAMesh Admin Settings
 * Provides branded settings page for markup configuration.
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_Admin {

  public function __construct() {
    // Hook into admin menu
    add_action('admin_menu', [$this, 'register_settings_page']);
    // Register settings
    add_action('admin_init', [$this, 'register_settings']);
  }

  /**
   * Register PTAMesh Settings page
   */
  public function register_settings_page() {
    add_menu_page(
      'PTAMesh Settings',             // Page title
      'PTAMesh Settings',             // Menu title
      'manage_options',               // Capability
      'ptamesh-settings',             // Menu slug
      [$this, 'render_settings_page'],// Callback
      'dashicons-admin-generic',      // Icon
      80                              // Position
    );
  }

  /**
   * Register settings fields
   */
  public function register_settings() {
    register_setting('ptamesh_settings', 'ptamesh_default_markup', [
      'type'              => 'number',
      'sanitize_callback' => function($val) {
        return max(0, min(1, floatval($val))); // clamp between 0 and 1
      },
      'default'           => 0.15, // 15% markup default
    ]);
  }

  /**
   * Render settings page (loads template)
   */
  public function render_settings_page() {
    include PTAMESH_DIR . 'templates/admin-settings.php';
  }
}
