<?php
/**
 * PTAMesh Admin Settings — hardened
 * Provides branded settings page for markup configuration.
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_Admin {

  public function __construct() {
    add_action('admin_menu', [$this, 'register_settings_page']);
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
      'default'           => 0.15,
    ]);

    add_settings_section(
      'ptamesh_main',
      'General Settings',
      function() {
        echo '<p>Configure default markup and other PTAMesh options.</p>';
      },
      'ptamesh-settings'
    );

    add_settings_field(
      'ptamesh_default_markup',
      'Default Markup (%)',
      [$this, 'render_markup_field'],
      'ptamesh-settings',
      'ptamesh_main'
    );
  }

  /**
   * Render markup field
   */
  public function render_markup_field() {
    $val = get_option('ptamesh_default_markup', 0.15);
    echo '<input type="number" step="0.01" min="0" max="1" name="ptamesh_default_markup" value="'.esc_attr($val).'" />';
    echo '<p class="description">Enter markup as a decimal (e.g., 0.15 = 15%).</p>';
  }

  /**
   * Render settings page
   */
  public function render_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>PTAMesh Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('ptamesh_settings');
    do_settings_sections('ptamesh-settings');
    submit_button();
    echo '</form>';
    echo '</div>';
  }
}