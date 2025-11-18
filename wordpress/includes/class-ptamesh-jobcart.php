<?php
/**
 * PTAMesh Job Cart Module
 * Registers Job Cart CPT, admin UI, and export actions.
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_JobCart {

  public function __construct() {
    add_action('init', [$this, 'register_cpt']);
    add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    add_action('save_post_jobcart', [$this, 'save_items']);
    add_action('admin_menu', [$this, 'register_export_page']);
  }

  /**
   * Register Job Cart CPT
   */
  public function register_cpt() {
    register_post_type('jobcart', [
      'labels' => [
        'name'          => 'Job Carts',
        'singular_name' => 'Job Cart',
      ],
      'public'      => false,
      'show_ui'     => true,
      'menu_icon'   => 'dashicons-clipboard',
      'supports'    => ['title'],
    ]);
  }

  /**
   * Register meta boxes
   */
  public function register_meta_boxes() {
    add_meta_box('ptamesh_items', 'Materials Used', [$this, 'render_items_box'], 'jobcart', 'normal', 'default');
    add_meta_box('ptamesh_labor', 'Labor Entries', [$this, 'render_labor_box'], 'jobcart', 'normal', 'default');
  }

  /**
   * Render materials meta box
   */
  public function render_items_box($post) {
    $items = get_post_meta($post->ID, '_ptamesh_items', true);
    if (!is_array($items)) { $items = []; }

    echo '<table class="widefat ptx-table"><thead><tr><th>Product ID</th><th>Qty</th><th>Unit Price</th></tr></thead><tbody>';
    foreach ($items as $i => $row) {
      echo '<tr>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][product_id]" value="'.esc_attr($row['product_id']).'"></td>';
      echo '<td><input type="number" name="ptamesh_items['.$i.'][qty]" value="'.esc_attr($row['qty']).'"></td>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][unit_price]" value="'.esc_attr($row['unit_price']).'"></td>';
      echo '</tr>';
    }
    echo '<tr><td><input type="text" name="ptamesh_items[new][product_id]"></td><td><input type="number" name="ptamesh_items[new][qty]"></td><td><input type="text" name="ptamesh_items[new][unit_price]"></td></tr>';
    echo '</tbody></table>';
  }

  /**
   * Render labor meta box
   */
  public function render_labor_box($post) {
    $labor = get_post_meta($post->ID, '_ptamesh_labor', true);
    if (!is_array($labor)) { $labor = []; }

    echo '<table class="widefat ptx-table"><thead><tr><th>Technician</th><th>Hours</th><th>Rate</th></tr></thead><tbody>';
    foreach ($labor as $i => $row) {
      echo '<tr>';
      echo '<td><input type="text" name="ptamesh_labor['.$i.'][tech]" value="'.esc_attr($row['tech']).'"></td>';
      echo '<td><input type="number" step="0.1" name="ptamesh_labor['.$i.'][hours]" value="'.esc_attr($row['hours']).'"></td>';
      echo '<td><input type="text" name="ptamesh_labor['.$i.'][rate]" value="'.esc_attr($row['rate']).'"></td>';
      echo '</tr>';
    }
    echo '<tr><td><input type="text" name="ptamesh_labor[new][tech]"></td><td><input type="number" step="0.1" name="ptamesh_labor[new][hours]"></td><td><input type="text" name="ptamesh_labor[new][rate]"></td></tr>';
    echo '</tbody></table>';
  }

  /**
   * Save materials and labor
   */
  public function save_items($post_id) {
    if (isset($_POST['ptamesh_items'])) {
      $items = array_filter($_POST['ptamesh_items'], fn($row) => !empty($row['product_id']));
      update_post_meta($post_id, '_ptamesh_items', $items);
    }
    if (isset($_POST['ptamesh_labor'])) {
      $labor = array_filter($_POST['ptamesh_labor'], fn($row) => !empty($row['tech']));
      update_post_meta($post_id, '_ptamesh_labor', $labor);
    }
  }

  /**
   * Register Export page
   */
  public function register_export_page() {
    add_submenu_page(
      'edit.php?post_type=jobcart',
      'Export Job Cart',
      'Export',
      'manage_options',
      'ptamesh-export',
      [$this, 'render_export_page']
    );
  }

  /**
   * Render Export page
   */
  public function render_export_page() {
    echo '<div class="wrap"><h1>Export Job Cart</h1>';

    $jobs = get_posts(['post_type' => 'jobcart', 'numberposts' => -1]);
    echo '<form method="post" action="">';
    echo '<select name="job_id">';
    foreach ($jobs as $job) {
      echo '<option value="'.$job->ID.'">'.esc_html($job->post_title).'</option>';
    }
    echo '</select>';

    echo '<p>';
    echo '<button type="submit" name="export_csv" class="button">Export CSV</button> ';
    echo '<button type="submit" name="export_pdf" class="button ptx-primary">Export PDF</button> ';
    echo '<button type="submit" name="export_sprout" class="button ptx-success">Export to Sprout</button>';
    echo '</p>';
    echo '</form>';

    if (!empty($_POST['job_id'])) {
      $job_id = intval($_POST['job_id']);
      $items  = get_post_meta($job_id, '_ptamesh_items', true);
      $labor  = get_post_meta($job_id, '_ptamesh_labor', true);
      if (!is_array($items)) { $items = []; }
      if (!is_array($labor)) { $labor = []; }

      // CSV export
      if (!empty($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="jobcart-'.$job_id.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Type','Desc','Qty','Rate','Total']);
        foreach ($items as $row) {
          $p = wc_get_product(intval($row['product_id']));
          $name = $p ? $p->get_name() : 'Unknown';
          $qty = intval($row['qty']);
          $unit = floatval($row['unit_price']);
          $unit_with_markup = PTAMesh_Pricing::apply_markup($unit);
          $line = PTAMesh_Pricing::line_total($unit, $qty);
          fputcsv($out, ['Material',$name,$qty,$unit_with_markup,$line]);
        }
        foreach ($labor as $row) {
          $tech = $row['tech'];
          $hours = floatval($row['hours']);
          $rate = floatval($row['rate']);
          $line = round($hours * $rate,2);
          fputcsv($out, ['Labor',$tech,$hours,$rate,$line]);
        }
        fclose($out);
        exit;
      }

      // PDF export
      if (!empty($_POST['export_pdf'])) {
        include