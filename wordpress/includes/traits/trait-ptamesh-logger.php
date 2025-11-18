<?php
trait PTAMesh_Logger {
  protected function log($message, $context = []) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[PTAMesh] ' . $message . (empty($context) ? '' : ' ' . json_encode($context)));
    }
  }
}
