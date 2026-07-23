<?php
/**
 * Security Layer class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Security {

    public function __construct() {
        // Constructor
    }

    public function validate_request($message, $session_id, $ip) {
        if (!$this->is_valid_session_id($session_id)) {
            return array(
                'valid' => false,
                'status' => 400,
                'error' => __('Invalid chat session', 'gsxr-777-ai-open-chat')
            );
        }

        if (!is_string($message)) {
            return array(
                'valid' => false,
                'status' => 400,
                'error' => __('Message must be text', 'gsxr-777-ai-open-chat')
            );
        }

        $sanitized_message = $this->sanitize_message($message);
        $message_length = function_exists('mb_strlen')
            ? mb_strlen($sanitized_message, 'UTF-8')
            : strlen($sanitized_message);

        if ($sanitized_message === '' || $message_length > 5000) {
            return array(
                'valid' => false,
                'status' => 400,
                'error' => __('Message is empty or too long', 'gsxr-777-ai-open-chat')
            );
        }

        // Check if IP is blocked
        if ($this->is_ip_blocked($ip)) {
            return array(
                'valid' => false,
                'status' => 429,
                'error' => __('Your IP address is temporarily blocked', 'gsxr-777-ai-open-chat')
            );
        }

        // Check atomic IP and session limits.
        if (!$this->check_rate_limit($ip, $session_id)) {
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $ip,
                'session_id' => $session_id
            ));
            
            return array(
                'valid' => false,
                'status' => 429,
                'error' => __('Rate limit exceeded. Please wait before sending another message', 'gsxr-777-ai-open-chat')
            );
        }

        return array(
            'valid' => true,
            'status' => 200,
            'sanitized_message' => $sanitized_message
        );
    }

    public function is_valid_session_id($session_id) {
        return is_string($session_id)
            && preg_match('/^gsxr777_[a-f0-9]{32}$/', $session_id) === 1;
    }

    public function check_rate_limit($ip, $session_id = '') {
        $requests_limit = max(1, intval(get_option('gsxr_777_rate_limit_requests', 10)));
        $window_seconds = max(10, intval(get_option('gsxr_777_rate_limit_window', 60)));
        $ip_limit = max(
            $requests_limit,
            intval(apply_filters('gsxr_777_ip_rate_limit', $requests_limit * 5, $requests_limit))
        );

        if (!$this->consume_rate_limit('ip', $ip, $ip_limit, $window_seconds)) {
            return false;
        }

        if ($session_id !== '' && !$this->consume_rate_limit('session', $session_id, $requests_limit, $window_seconds)) {
            return false;
        }

        return true;
    }

    private function consume_rate_limit($scope, $identifier, $limit, $window_seconds) {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_rate_limits');
        $window = (int) floor(time() / $window_seconds);
        $rate_key = hash_hmac(
            'sha256',
            $scope . '|' . $identifier . '|' . $window,
            wp_salt('secure_auth')
        );
        $expires_at = gmdate('Y-m-d H:i:s', (($window + 1) * $window_seconds) + HOUR_IN_SECONDS);

        $updated = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table_name} (rate_key, request_count, expires_at, updated_at)
             VALUES (%s, 1, %s, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                request_count = request_count + 1,
                expires_at = VALUES(expires_at),
                updated_at = UTC_TIMESTAMP()",
            $rate_key,
            $expires_at
        ));

        if ($updated === false) {
            // Fail closed when accounting cannot be performed.
            error_log('GSXR-777: Rate limit storage error');
            return false;
        }

        $count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT request_count FROM {$table_name} WHERE rate_key = %s",
            $rate_key
        )));

        return $count <= $limit;
    }

    public function block_ip($ip, $duration = 3600) {
        global $wpdb;

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $duration = max(MINUTE_IN_SECONDS, intval($duration));
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_blocked_ips');
        $expires_at = gmdate('Y-m-d H:i:s', time() + $duration);

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table_name} (ip_address, reason, blocked_at, expires_at)
             VALUES (%s, %s, UTC_TIMESTAMP(), %s)
             ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                blocked_at = UTC_TIMESTAMP(),
                expires_at = VALUES(expires_at)",
            $ip,
            'Repeated invalid requests',
            $expires_at
        ));
        
        $this->log_security_event('ip_blocked', array(
            'ip' => $ip,
            'duration' => $duration
        ));

        return $result !== false;
    }

    public function is_ip_blocked($ip) {
        global $wpdb;

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_blocked_ips');
        $expires_at = $wpdb->get_var($wpdb->prepare(
            "SELECT expires_at FROM {$table_name} WHERE ip_address = %s",
            $ip
        ));

        if (!$expires_at) {
            return false;
        }

        if (strtotime($expires_at . ' UTC') > time()) {
            return true;
        }

        $wpdb->delete($table_name, array('ip_address' => $ip), array('%s'));
        return false;
    }

    public function sanitize_message($message) {
        // Remove null bytes
        $message = str_replace("\0", '', $message);
        
        // Normalize whitespace
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Trim
        $message = trim($message);
        
        // Remove control characters except newlines and tabs
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message);
        
        return $message;
    }

    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    private function log_security_event($event_type, $details) {
        global $wpdb;
        
        try {
            $table_name = esc_sql($wpdb->prefix . 'gsxr777_security_log');
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'ip_address' => isset($details['ip']) ? $this->anonymize_ip($details['ip']) : '',
                    'event_type' => $event_type,
                    'details' => wp_json_encode($this->sanitize_log_details($details)),
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s')
            );
            
            if ($result === false && !empty($wpdb->last_error)) {
                error_log('GSXR-777: Failed to log security event - ' . $wpdb->last_error);
            }
        } catch (Exception $e) {
            error_log('GSXR-777: Security log error - ' . $e->getMessage());
        }
    }

    public function get_security_stats($days = 7) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_security_log');
        $days = max(1, intval($days));
        $date_from = gmdate('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM {$table_name} 
             WHERE created_at >= %s 
             GROUP BY event_type 
             ORDER BY count DESC",
            $date_from
        ));
        
        return $stats;
    }

    public function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_security_log');
        $days = max(1, intval($days));
        $date_from = gmdate('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            $date_from
        ));
        
        return $deleted;
    }

    public function cleanup_rate_limits() {
        global $wpdb;

        $rate_limits_table = esc_sql($wpdb->prefix . 'gsxr777_rate_limits');
        $blocked_ips_table = esc_sql($wpdb->prefix . 'gsxr777_blocked_ips');

        $wpdb->query("DELETE FROM {$rate_limits_table} WHERE expires_at < UTC_TIMESTAMP()");
        $wpdb->query("DELETE FROM {$blocked_ips_table} WHERE expires_at IS NOT NULL AND expires_at < UTC_TIMESTAMP()");
    }

    private function anonymize_ip($ip) {
        if (function_exists('wp_privacy_anonymize_ip')) {
            return wp_privacy_anonymize_ip($ip);
        }

        return preg_replace('/\d+$/', '0', (string) $ip);
    }

    private function sanitize_log_details($details) {
        $safe = array();
        $allowed_keys = array('ip', 'session_id', 'attack_type', 'duration');

        foreach ($allowed_keys as $key) {
            if (!isset($details[$key])) {
                continue;
            }

            $value = $details[$key];
            if ($key === 'ip') {
                $value = $this->anonymize_ip($value);
            }
            $safe[$key] = sanitize_text_field((string) $value);
        }

        return $safe;
    }
}
