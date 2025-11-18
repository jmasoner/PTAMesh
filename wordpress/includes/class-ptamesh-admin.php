<?php
class PTAMesh_Admin {
  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  public function __construct() {
    // Placeholder for settings page (auth, defaults, markup)
    add_action('admin_init', [$this, 'register_settings']);
  }

  public function register_settings() {
    register_setting('ptamesh', 'ptamesh_markup_default', [
      'type' => 'number', 'default' => 40, 'sanitize_callback' => 'floatval'
    ]);
    add_settings_section('ptamesh_main', 'PTAMesh Settings', function(){
      echo '<p>Default markup and behavior for internal materials.</p>';
    }, 'ptamesh');
    add_settings_field('ptamesh_markup_default', 'Default Markup (%)', function(){
      $val = get_option('ptamesh_markup_default', 40);
      echo '<input type="number" name="ptamesh_markup_default" value="'.esc_attr($val).'" min="0" step="0.1"/>';
    }, 'ptamesh', 'ptamesh_main');
  }
}
