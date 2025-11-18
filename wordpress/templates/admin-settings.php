<?php
/**
 * Template: PTAMesh Settings Page
 * Location: wordpress/templates/admin-settings.php
 */

if (!defined('ABSPATH')) { exit; }

$logo_url = 'https://protraxer.com/wp-content/uploads/2025/09/ProTraxer-Logo.png';
$markup   = get_option('ptamesh_default_markup', 0.15);
?>
<div class="wrap">
  <div class="ptx-brand-bar">
    <img src="<?php echo esc_url($logo_url); ?>" alt="ProTraxer Logo">
    <div class="ptx-title">PTAMesh — Settings</div>
  </div>

  <h1>Default Markup</h1>
  <form method="post" action="options.php">
    <?php settings_fields('ptamesh_settings'); ?>
    <?php do_settings_sections('ptamesh_settings'); ?>
    <table class="form-table">
      <tr>
        <th scope="row">Default Markup (%)</th>
        <td>
          <input type="number" step="0.01" name="ptamesh_default_markup" value="<?php echo esc_attr($markup*100); ?>"> %
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form>
</div>
