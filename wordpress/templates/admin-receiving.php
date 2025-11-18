<?php
/**
 * Template: Admin Receiving Page
 * Location: wordpress/templates/admin-receiving.php
 */

if (!defined('ABSPATH')) { exit; }

$logo_url = 'https://protraxer.com/wp-content/uploads/2025/09/ProTraxer-Logo.png';
?>
<div class="wrap">
  <div class="ptx-brand-bar">
    <img src="<?php echo esc_url($logo_url); ?>" alt="ProTraxer Logo">
    <div class="ptx-title">PTAMesh — Receiving</div>
  </div>

  <h1>Items On Order</h1>

  <form method="post" action="">
    <table class="widefat ptx-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Qty Ordered</th>
          <th>Qty Received</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $items = get_option('ptamesh_on_order', []);
        foreach ($items as $i => $row) {
          $p = wc_get_product(intval($row['product_id']));
          $name = $p ? $p->get_name() : 'Unknown';
          echo '<tr>';
          echo '<td>' . esc_html($name) . '</td>';
          echo '<td>' . esc_html($row['qty']) . '</td>';
          echo '<td><input type="number" name="received['.$i.']" value="0" min="0" max="'.esc_attr($row['qty']).'"></td>';
          echo '<td><button type="submit" name="mark_received['.$i.']" class="button ptx-success">Mark Received</button></td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  </form>
</div>
