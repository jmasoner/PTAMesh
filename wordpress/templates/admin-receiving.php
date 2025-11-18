<?php
echo '<div class="wrap"><h1>PTAMesh Receiving</h1><p>Mark incoming items as received to increase stock.</p>';
echo '<table class="widefat"><thead><tr><th>Product</th><th>ASIN</th><th>On Order</th><th>Action</th></tr></thead><tbody>';
if ($query->have_posts()) {
  while ($query->have_posts()) { $query->the_post();
    $pid = get_the_ID();
    $asin = get_post_meta($pid, PTAMesh_Products::META_ASIN, true);
    $on_order = intval(get_post_meta($pid, '_ptamesh_on_order', true));
    echo '<tr>';
    echo '<td>' . esc_html(get_the_title()) . '</td>';
    echo '<td>' . esc_html($asin) . '</td>';
    echo '<td>' . esc_html($on_order) . '</td>';
    echo '<td>';
    echo '<form method="post">';
    wp_nonce_field('ptamesh_receive_action');
    echo '<input type="hidden" name="product_id" value="' . esc_attr($pid) . '"/>';
    echo '<input type="number" name="qty" min="1" max="' . esc_attr($on_order) . '" value="' . esc_attr($on_order) . '"/>';
    echo '<button class="button button-primary" name="ptamesh_receive" value="1">Receive</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
  }
  wp_reset_postdata();
} else {
  echo '<tr><td colspan="4">No items on order.</td></tr>';
}
echo '</tbody></table></div>';
