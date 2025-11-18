<?php
class PTAMesh_JobCart {
  use PTAMesh_Logger;

  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  public function __construct() {
    add_action('init', [$this, 'register_cpt']);
    add_action('add_meta_boxes', [$this, 'meta_boxes']);
    add_action('save_post_ptamesh_job', [$this, 'save_job'], 10, 2);
    add_action('admin_menu', [$this, 'add_export_actions']);
  }

  public function register_cpt() {
    register_post_type('ptamesh_job', [
      'labels' => ['name' => 'Job Carts', 'singular_name' => 'Job Cart'],
      'public' => false,
      'show_ui' => true,
      'menu_icon' => 'dashicons-clipboard',
      'supports' => ['title', 'editor'],
    ]);
  }

  public function meta_boxes() {
    add_meta_box('ptamesh_job_items', 'Materials Used', [$this, 'render_items_box'], 'ptamesh_job', 'normal', 'high');
  }

  public function render_items_box($post) {
    $items = get_post_meta($post->ID, '_ptamesh_items', true);
    if (!is_array($items)) { $items = []; }

    echo '<p>Add inventory items used on this job. Pricing applies 40% markup by default.</p>';
    echo '<div id="ptamesh-items">';
    echo '<table class="widefat"><thead><tr><th>Product (search by SKU/ASIN)</th><th>Qty</th><th>Unit Cost</th><th>Unit Price</th><th>Total</th></tr></thead><tbody>';

    foreach ($items as $i => $row) {
      $p = wc_get_product($row['product_id']);
      $name = $p ? $p->get_name() : 'Unknown';
      $unit_cost = floatval($row['unit_cost']);
      $unit_price = floatval($row['unit_price']);
      $qty = intval($row['qty']);
      echo '<tr>';
      echo '<td>' . esc_html($name) . '<input type="hidden" name="ptamesh_items['.$i.'][product_id]" value="'.esc_attr($row['product_id']).'"/></td>';
      echo '<td><input type="number" name="ptamesh_items['.$i.'][qty]" value="'.esc_attr($qty).'" min="1"/></td>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][unit_cost]" value="'.esc_attr($unit_cost).'"/></td>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][unit_price]" value="'.esc_attr($unit_price).'"/></td>';
      echo '<td>' . number_format($qty * $unit_price, 2) . '</td>';
      echo '</tr>';
    }

    echo '</tbody></table></div>';

    echo '<p><button class="button" id="ptamesh-add-item">Add Item</button></p>';
    echo '<script>
      jQuery(function($){
        $("#ptamesh-add-item").on("click", function(e){
          e.preventDefault();
          var row = `
            <tr>
              <td><input type="text" name="ptamesh_items_new[search]" placeholder="Search SKU/ASIN/Name"/></td>
              <td><input type="number" name="ptamesh_items_new[qty]" min="1" value="1"/></td>
              <td><input type="text" name="ptamesh_items_new[unit_cost]" value=""/></td>
              <td><input type="text" name="ptamesh_items_new[unit_price]" value=""/></td>
              <td>-</td>
            </tr>`;
          $("#ptamesh-items tbody").append(row);
        });
      });
    </script>';
  }

  public function save_job($post_id, $post) {
    if (isset($_POST['ptamesh_items'])) {
      update_post_meta($post_id, '_ptamesh_items', array_map(function($row){
        return [
          'product_id' => intval($row['product_id']),
          'qty' => intval($row['qty']),
          'unit_cost' => floatval($row['unit_cost']),
          'unit_price' => floatval($row['unit_price']),
        ];
      }, $_POST['ptamesh_items']));
    }
    // Handle new item via search later (Day-1 can be manual)
  }

  public function add_export_actions() {
    add_submenu_page('edit.php?post_type=ptamesh_job', 'Export', 'Export', 'manage_woocommerce', 'ptamesh_export', [$this, 'render_export_page']);
  }

  public function render_export_page() {
    echo '<div class="wrap"><h1>Export Job Cart</h1><p>Select a Job Cart to export as PDF or CSV.</p>';
    $jobs = get_posts(['post_type' => 'ptamesh_job', 'numberposts' => 50]);
    echo '<form method="post">';
    echo '<select name="job_id">';
    foreach ($jobs as $job) { echo '<option value="'.esc_attr($job->ID).'">'.esc_html($job->post_title).'</option>'; }
    echo '</select> ';
    echo '<button class="button" name="export_csv" value="1">Export CSV</button> ';
    echo '<button class="button button-primary" name="export_pdf" value="1">Export PDF</button>';
    echo '</form></div>';

    if (!empty($_POST['job_id'])) {
      $job_id = intval($_POST['job_id']);
      $items = get_post_meta($job_id, '_ptamesh_items', true);
      if (!is_array($items)) { $items = []; }

      if (!empty($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=job_' . $job_id . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Product', 'Qty', 'Unit Cost', 'Unit Price', 'Line Total']);
        foreach ($items as $row) {
          $p = wc_get_product($row['product_id']);
          fputcsv($out, [
            $p ? $p->get_name() : 'Unknown',
            $row['qty'],
            $row['unit_cost'],
            $row['unit_price'],
            number_format($row['qty'] * $row['unit_price'], 2)
          ]);
        }
        fclose($out);
        exit;
      }

      if (!empty($_POST['export_pdf'])) {
        // Day-1: output HTML printable page; PDF can be added later
        echo '<h2>Printable Job Cart</h2><table class="widefat"><thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead><tbody>';
        foreach ($items as $row) {
          $p = wc_get_product($row['product_id']);
          echo '<tr><td>'.esc_html($p ? $p->get_name() : 'Unknown').'</td><td>'.esc_html($row['qty']).'</td><td>'.esc_html($row['unit_price']).'</td><td>'.number_format($row['qty']*$row['unit_price'],2).'</td></tr>';
        }
        echo '</tbody></table>';
      }
    }
  }
}
