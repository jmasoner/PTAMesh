<?php
class PTAMesh_Products {
  use PTAMesh_Logger;

  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  // Product meta keys (Gripper-ready identifiers)
  const META_ASIN = '_ptamesh_asin';
  const META_SUPPLIER_URL = '_ptamesh_supplier_url';
  const META_LAST_PURCHASE = '_ptamesh_last_purchase_price';
  const META_BIN = '_ptamesh_bin_location';
  const META_BARCODE = '_ptamesh_barcode';

  public function __construct() {
    // Register admin columns or tools later as needed
  }

  public function get_by_asin($asin) {
    $products = wc_get_products(['limit' => 1, 'meta_key' => self::META_ASIN, 'meta_value' => $asin]);
    return $products ? $products[0] : null;
  }

  public function create_or_update(array $payload) {
    /**
     * Payload schema:
     * asin (string, required), title, url, image, price, qty (int), sku?, bin?, barcode?
     */
    $asin = $payload['asin'] ?? '';
    if (!$asin) { throw new InvalidArgumentException('ASIN required'); }

    $product = $this->get_by_asin($asin);
    $is_new = !$product;

    if ($is_new) {
      $product = new WC_Product_Simple();
      $product->set_name($payload['title'] ?? ('ASIN ' . $asin));
      $product->set_status('publish');
      $product->set_catalog_visibility('hidden'); // internal use
      $product->set_manage_stock(true);
      $product->set_stock_quantity(0);
      $product->set_regular_price($payload['price'] ?? '');
      $product->set_sku($payload['sku'] ?? $asin);
    } else {
      if (!empty($payload['title'])) { $product->set_name($payload['title']); }
      if (!empty($payload['price'])) { $product->set_regular_price($payload['price']); }
    }

    $product->update_meta_data(self::META_ASIN, $asin);
    if (!empty($payload['url'])) { $product->update_meta_data(self::META_SUPPLIER_URL, $payload['url']); }
    if (!empty($payload['price'])) { $product->update_meta_data(self::META_LAST_PURCHASE, $payload['price']); }
    if (!empty($payload['bin'])) { $product->update_meta_data(self::META_BIN, $payload['bin']); }
    if (!empty($payload['barcode'])) { $product->update_meta_data(self::META_BARCODE, $payload['barcode']); }

    $product_id = $product->save();

    // Image (optional Day-1)
    if (!empty($payload['image'])) {
      // Leave as future: downloading and attaching image file
      $this->log('Image URL stored for later processing', ['product_id' => $product_id]);
    }

    // Mark "on order"
    $this->mark_on_order($product_id, intval($payload['qty'] ?? 1));

    return $product_id;
  }

  public function mark_on_order($product_id, $qty) {
    // Transaction log could be CPT or custom table; Day-1: meta increment
    $on_order = intval(get_post_meta($product_id, '_ptamesh_on_order', true));
    update_post_meta($product_id, '_ptamesh_on_order', $on_order + max(0, $qty));
    $this->log('Marked on order', ['product_id' => $product_id, 'qty' => $qty]);
  }

  public function receive($product_id, $qty) {
    $product = wc_get_product($product_id);
    if (!$product) { return false; }

    // Decrement on_order
    $on_order = intval(get_post_meta($product_id, '_ptamesh_on_order', true));
    update_post_meta($product_id, '_ptamesh_on_order', max(0, $on_order - $qty));

    // Increment stock
    $current = $product->get_stock_quantity();
    $product->set_stock_quantity(($current ?? 0) + $qty);
    $product->save();

    // Log
    $logs = get_post_meta($product_id, '_ptamesh_logs', true);
    if (!is_array($logs)) { $logs = []; }
    $logs[] = ['ts' => current_time('mysql'), 'event' => 'receive', 'qty' => $qty];
    update_post_meta($product_id, '_ptamesh_logs', $logs);

    return true;
  }
}
