<?php
trait PTAMesh_Security {
  protected function verify_nonce($nonce, $action) {
    return wp_verify_nonce($nonce, $action);
  }

  protected function require_cap($cap = 'manage_woocommerce') {
    if (!current_user_can($cap)) {
      wp_die('Insufficient permissions.');
    }
  }

  protected function sanitize_text($val) { return sanitize_text_field($val); }
  protected function sanitize_url($val) { return esc_url_raw($val); }
  protected function sanitize_float($val) { return floatval($val); }
  protected function sanitize_int($val) { return intval($val); }
}
