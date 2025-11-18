<?php
class PTAMesh_REST {
  use PTAMesh_Logger, PTAMesh_Security;

  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  public function __construct() {
    add_action('rest_api_init', [$this, 'register']);
  }

  public function register() {
    register_rest_route('ptamesh/v1', '/add-product', [
      'methods' => 'POST',
      'callback' => [$this, 'add_product'],
      'permission_callback' => [$this, 'permission'],
      'args' => [
        'asin' => ['required' => true],
        'title' => [],
        'url' => [],
        'image' => [],
        'price' => [],
        'qty' => [],
        'sku' => [],
        'bin' => [],
        'barcode' => [],
      ],
    ]);

    register_rest_route('ptamesh/v1', '/receive', [
      'methods' => 'POST',
      'callback' => [$this, 'receive'],
      'permission_callback' => [$this, 'permission'],
      'args' => [
        'product_id' => ['required' => true],
        'qty' => ['required' => true],
      ],
    ]);
  }

  public function permission() {
    // For Day-1: require logged-in user with manage_woocommerce
    return current_user_can('manage_woocommerce');
    // For extension auth later: application password or JWT
  }

  public function add_product(WP_REST_Request $req) {
    try {
      $payload = [
        'asin' => sanitize_text_field($req['asin']),
        'title' => sanitize_text_field($req['title']),
        'url' => esc_url_raw($req['url']),
        'image' => esc_url_raw($req['image']),
        'price' => sanitize_text_field($req['price']),
        'qty' => intval($req['qty']),
        'sku' => sanitize_text_field($req['sku']),
        'bin' => sanitize_text_field($req['bin']),
        'barcode' => sanitize_text_field($req['barcode']),
      ];
      $id = PTAMesh_Products::instance()->create_or_update($payload);
      return new WP_REST_Response(['ok' => true, 'product_id' => $id], 200);
    } catch (Throwable $e) {
      $this->log('add_product error', ['err' => $e->getMessage()]);
      return new WP_REST_Response(['ok' => false, 'error' => $e->getMessage()], 400);
    }
  }

  public function receive(WP_REST_Request $req) {
    $pid = intval($req['product_id']);
    $qty = intval($req['qty']);
    $ok = PTAMesh_Products::instance()->receive($pid, $qty);
    return new WP_REST_Response(['ok' => $ok], $ok ? 200 : 400);
  }
}
