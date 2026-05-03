<?php
/**
 * Security Layer class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Security {

    private $blocked_ips_transient = 'gsxr_777_blocked_ips';
    private $rate_limit_transient = 'gsxr_777_rate_limit_';

    public function __construct() {
        // Constructor
    }

    public function validate_request($message, $session_id, $ip) {
        // Check if IP is blocked
        if ($this->is_ip_blocked($ip)) {
            return array(
                'valid' => false,
                'error' => __('Your IP address is temporarily blocked', 'gsxr-777')
            );
        }

        // Check rate limit
        if (!$this->check_rate_limit($ip)) {
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $ip,
                'session_id' => $session_id
            ));
            
            return array(
                'valid' => false,
                'error' => __('Rate limit exceeded. Please wait before sending another message', 'gsxr-777')
            );
        }

        // Sanitize message
        $sanitized_message = $this->sanitize_message($message);
        
        // Detect attacks
        $attack_detected = $this->detect_attack($sanitized_message);
        if ($attack_detected) {
            $this->log_security_event('attack_detected', array(
                'ip' => $ip,
                'session_id' => $session_id,
                'attack_type' => $attack_detected,
                'message' => substr($message, 0, 500)
            ));
            
            // Block IP for repeated attacks
            $this->increment_strike($ip);
            
            return array(
                'valid' => false,
                'error' => __('Message contains suspicious content', 'gsxr-777')
            );
        }

        return array(
            'valid' => true,
            'sanitized_message' => $sanitized_message
        );
    }

    public function check_rate_limit($ip) {
        $requests_limit = get_option('gsxr_777_rate_limit_requests', 10);
        $window_seconds = get_option('gsxr_777_rate_limit_window', 60);
        
        $transient_key = $this->rate_limit_transient . md5($ip);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            // First request in window
            set_transient($transient_key, 1, $window_seconds);
            return true;
        }
        
        if ($requests >= $requests_limit) {
            return false;
        }
        
        // Increment request count
        set_transient($transient_key, $requests + 1, $window_seconds);
        return true;
    }

    public function detect_attack($message) {
        $message_lower = strtolower($message);
        
        // SQL Injection patterns
        $sql_patterns = array(
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+set/i',
            '/exec\s*\(/i',
            '/script\s*>/i'
        );
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'sql_injection';
            }
        }
        
        // XSS patterns
        $xss_patterns = array(
            '/<script[^>]*>/i',
            '/<iframe[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<img[^>]*onerror/i'
        );
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'xss_attempt';
            }
        }
        
        // Prompt injection patterns
        $prompt_patterns = array(
            '/ignore\s+previous\s+instructions/i',
            '/forget\s+everything/i',
            '/you\s+are\s+now/i',
            '/system\s*:\s*you/i',
            '/jailbreak/i',
            '/pretend\s+to\s+be/i'
        );
        
        foreach ($prompt_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'prompt_injection';
            }
        }
        
        // Check message length (prevent DoS)
        if (strlen($message) > 5000) {
            return 'message_too_long';
        }
        
        return false;
    }

    public function block_ip($ip, $duration = 3600) {
        $blocked_ips = get_transient($this->blocked_ips_transient);
        if ($blocked_ips === false) {
            $blocked_ips = array();
        }
        
        $blocked_ips[$ip] = time() + $duration;
        set_transient($this->blocked_ips_transient, $blocked_ips, $duration);
        
        $this->log_security_event('ip_blocked', array(
            'ip' => $ip,
            'duration' => $duration
        ));
    }

    public function is_ip_blocked($ip) {
        $blocked_ips = get_transient($this->blocked_ips_transient);
        if ($blocked_ips === false) {
            return false;
        }
        
        if (isset($blocked_ips[$ip])) {
            if ($blocked_ips[$ip] > time()) {
                return true;
            } else {
                // Remove expired block
                unset($blocked_ips[$ip]);
                set_transient($this->blocked_ips_transient, $blocked_ips, 3600);
            }
        }
        
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

    private function increment_strike($ip) {
        $strikes_key = 'gsxr_777_strikes_' . md5($ip);
        $strikes = get_transient($strikes_key);
        
        if ($strikes === false) {
            $strikes = 1;
        } else {
            $strikes++;
        }
        
        // Set strikes with 1 hour expiry
        set_transient($strikes_key, $strikes, 3600);
        
        // Block IP based on strike count (fixed logic - check highest first)
        if ($strikes >= 10) {
            $this->block_ip($ip, 86400);  // 24 hour block
        } elseif ($strikes >= 5) {
            $this->block_ip($ip, 7200);   // 2 hour block
        } elseif ($strikes >= 3) {
            $this->block_ip($ip, 3600);   // 1 hour block
        }
    }

    private function log_security_event($event_type, $details) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'gsxr777_security_log';
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'ip_address' => isset($details['ip']) ? $details['ip'] : '',
                    'event_type' => $event_type,
                    'details' => json_encode($details),
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
        
        $table_name = $wpdb->prefix . 'gsxr777_security_log';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
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
        
        $table_name = $wpdb->prefix . 'gsxr777_security_log';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            $date_from
        ));
        
        return $deleted;
    }
}