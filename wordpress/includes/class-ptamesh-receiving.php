<?php
class PTAMesh_Receiving {
  use PTAMesh_Logger;

  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  public function __construct() {
    add_action('admin_menu', [$this, 'menu']);
  }

  public function menu() {
    add_menu_page(
      'PTAMesh Inventory',
      'PTAMesh',
      'manage_woocommerce',
      'ptamesh',
      [$this, 'render_receiving'],
      'dashicons-archive',
      56
    );
    add_submenu_page('ptamesh', 'Receiving', 'Receiving', 'manage_woocommerce', 'ptamesh', [$this, 'render_receiving']);
  }

  public function render_receiving() {
    if (!empty($_POST['ptamesh_receive'])) {
      check_admin_referer('ptamesh_receive_action');
      $pid = intval($_POST['product_id']);
      $qty = intval($_POST['qty']);
      PTAMesh_Products::instance()->receive($pid, $qty);
      echo '<div class="notice notice-success"><p>Received ' . esc_html($qty) . ' units.</p></div>';
    }

    $query = new WP_Query([
      'post_type' => 'product',
      'posts_per_page' => 50,
      'meta_query' => [
        ['key' => '_ptamesh_on_order', 'compare' => '>', 'value' => 0, 'type' => 'NUMERIC']
      ]
    ]);

    include PTAMESH_DIR . 'templates/admin-receiving.php';
  }
}
