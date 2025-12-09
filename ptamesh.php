<?php
/**
 * Plugin Name: PTA-Mesh Bridge
 * Description: Receives Amazon product data from PTA-Extension and creates WooCommerce products via REST.
 * Version: 0.1.2
 * Author: John Masoner
 * Author URI: https://protraxer.com
 * License: GPL2
 */

add_action('rest_api_init', function () {
    register_rest_route('protraxer/v1', '/import-amazon', array(
        'methods' => 'POST',
        'callback' => 'pta_mesh_import_amazon',
        'permission_callback' => '__return_true', // TODO: Replace with auth check in v0.2.0
    ));
});

/**
 * PTA-Mesh v0.1.2 — Import handler for Amazon product data
 */
function pta_mesh_import_amazon($request) {
    $data = $request->get_json_params();

    $title = sanitize_text_field($data['title'] ?? 'Untitled Product');
    $description = wp_kses_post($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $asin = sanitize_text_field($data['asin'] ?? '');
    $images = $data['images'] ?? [];
    $keywords = sanitize_text_field($data['keywords'] ?? '');
    $meta_title = sanitize_text_field($data['meta_title'] ?? '');
    $meta_description = sanitize_text_field($data['meta_description'] ?? '');

    $product = new WC_Product_Simple();
    $product->set_name($title);
    $product->set_description($description);
    $product->set_regular_price($price);
    $product->set_sku($asin);
    $product->set_status('publish');

    // Handle multiple images
    if (!empty($images)) {
        $gallery_ids = [];
        foreach ($images as $i => $url) {
            $image_id = pta_mesh_sideload_image($url, $title);
            if ($image_id) {
                if ($i === 0) {
                    $product->set_image_id($image_id); // main image
                } else {
                    $gallery_ids[] = $image_id; // gallery images
                }
            }
        }
        if ($gallery_ids) {
            $product->set_gallery_image_ids($gallery_ids);
        }
    }

    $product_id = $product->save();

    return rest_ensure_response([
        'success' => true,
        'product_id' => $product_id,
        'message' => 'Product imported successfully with images.'
    ]);
}

/**
 * PTA-Mesh v0.1.2 — Sideload image from URL
 */
function pta_mesh_sideload_image($url, $title) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $tmp = download_url($url);
    if (is_wp_error($tmp)) return 0;

    $file_array = [
        'name' => basename($url),
        'tmp_name' => $tmp
    ];

    $id = media_handle_sideload($file_array, 0, $title);
    return is_wp_error($id) ? 0 : $id;
}