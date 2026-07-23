<?php
/**
 * Statistics Module class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Stats {

    public function __construct() {
        // Constructor
    }

    public function track_session($session_id, $ip_address = '', $user_agent = '') {
        global $wpdb;
        
        try {
            $table_name = esc_sql($wpdb->prefix . 'gsxr777_sessions');

            $result = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$table_name}
                    (session_id, ip_address, user_agent, created_at, last_activity)
                 VALUES (%s, %s, %s, %s, %s)
                 ON DUPLICATE KEY UPDATE
                    last_activity = VALUES(last_activity)",
                $session_id,
                sanitize_text_field($ip_address),
                sanitize_text_field($user_agent),
                current_time('mysql'),
                current_time('mysql')
            ));

            if ($result === false && !empty($wpdb->last_error)) {
                error_log('GSXR-777: Failed to store session');
            }
        } catch (Exception $e) {
            error_log('GSXR-777: Session tracking error - ' . $e->getMessage());
        }
    }

    public function track_message($session_id, $role, $content, $tokens_used = 0) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'role' => $role,
                'content' => $content,
                'tokens_used' => $tokens_used,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }

    public function delete_session($session_id) {
        global $wpdb;

        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');

        $wpdb->delete($messages_table, array('session_id' => $session_id), array('%s'));
        return $wpdb->delete($sessions_table, array('session_id' => $session_id), array('%s'));
    }

    public function get_stats($period = 30) {
        $stats = array(
            'total_sessions' => $this->get_total_sessions($period),
            'total_messages' => $this->get_total_messages($period),
            'average_messages_per_session' => $this->get_average_messages_per_session($period),
            'total_tokens_used' => $this->get_total_tokens_used($period),
            'activity_by_day' => $this->get_activity_chart_data($period)
        );
        
        return $stats;
    }

    public function get_total_sessions($period = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $date_from = $this->get_period_start($period);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
            $date_from
        ));
        
        return intval($count);
    }

    public function get_total_messages($period = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = $this->get_period_start($period);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
            $date_from
        ));
        
        return intval($count);
    }

    public function get_average_messages_per_session($period = 30) {
        global $wpdb;
        
        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = $this->get_period_start($period);
        
        $avg = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(message_count) FROM (
                SELECT s.session_id, COUNT(m.id) as message_count
                FROM {$sessions_table} s
                LEFT JOIN {$messages_table} m ON s.session_id = m.session_id
                WHERE s.created_at >= %s
                GROUP BY s.session_id
            ) as session_stats",
            $date_from
        ));
        
        return floatval($avg);
    }

    public function get_total_tokens_used($period = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = $this->get_period_start($period);
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(tokens_used) FROM {$table_name} WHERE created_at >= %s",
            $date_from
        ));
        
        return intval($total);
    }

    public function get_activity_chart_data($period = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = gmdate('Y-m-d', current_time('timestamp') - (max(1, intval($period)) * DAY_IN_SECONDS));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as messages
             FROM {$table_name} 
             WHERE DATE(created_at) >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $date_from
        ));
        
        // Fill in missing dates with zero values
        $data = array();
        $current_date = strtotime($date_from);
        $end_date = current_time('timestamp');
        
        while ($current_date <= $end_date) {
            $date_str = gmdate('Y-m-d', $current_date);
            $data[$date_str] = 0;
            $current_date = strtotime('+1 day', $current_date);
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            $data[$row->date] = intval($row->messages);
        }
        
        return $data;
    }

    public function get_popular_times($period = 30) {
        global $wpdb;
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = $this->get_period_start($period);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as messages
             FROM {$table_name} 
             WHERE created_at >= %s
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC",
            $date_from
        ));
        
        $data = array();
        for ($i = 0; $i < 24; $i++) {
            $data[$i] = 0;
        }
        
        foreach ($results as $row) {
            $data[intval($row->hour)] = intval($row->messages);
        }
        
        return $data;
    }

    public function get_session_details($session_id) {
        global $wpdb;
        
        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');
        
        // Get session info
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions_table} WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return false;
        }
        
        // Get messages
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$messages_table} 
             WHERE session_id = %s 
             ORDER BY created_at ASC",
            $session_id
        ));
        
        return array(
            'session' => $session,
            'messages' => $messages
        );
    }

    public function get_recent_sessions($limit = 10) {
        global $wpdb;
        
        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, COUNT(m.id) as message_count
             FROM {$sessions_table} s
             LEFT JOIN {$messages_table} m ON s.session_id = m.session_id
             GROUP BY s.id
             ORDER BY s.last_activity DESC
             LIMIT %d",
            $limit
        ));
        
        return $results;
    }

    public function cleanup_old_data($days = 90) {
        global $wpdb;
        
        // Validate days parameter
        $days = max(1, intval($days));
        
        $sessions_table = esc_sql($wpdb->prefix . 'gsxr777_sessions');
        $messages_table = esc_sql($wpdb->prefix . 'gsxr777_messages');
        $date_from = $this->get_period_start($days);
        
        try {
            // Delete messages using INNER JOIN for better performance and safety
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE m FROM {$messages_table} m
                 INNER JOIN {$sessions_table} s ON m.session_id = s.session_id
                 WHERE s.last_activity < %s",
                $date_from
            ));
            
            // Delete old sessions
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$sessions_table} WHERE last_activity < %s",
                $date_from
            ));

            // Remove any orphaned messages left by interrupted upgrades or imports.
            $wpdb->query(
                "DELETE m FROM {$messages_table} m
                 LEFT JOIN {$sessions_table} s ON m.session_id = s.session_id
                 WHERE s.id IS NULL"
            );
            
            return $deleted;
        } catch (Exception $e) {
            error_log('GSXR-777: Cleanup error - ' . $e->getMessage());
            return 0;
        }
    }

    public function export_stats($period = 30, $format = 'json') {
        $stats = $this->get_stats($period);
        
        if ($format === 'csv') {
            return $this->export_to_csv($stats);
        }
        
        return json_encode($stats, JSON_PRETTY_PRINT);
    }

    private function export_to_csv($stats) {
        $csv = "Metric,Value\n";
        $csv .= "Total Sessions," . $stats['total_sessions'] . "\n";
        $csv .= "Total Messages," . $stats['total_messages'] . "\n";
        $csv .= "Average Messages per Session," . $stats['average_messages_per_session'] . "\n";
        $csv .= "Total Tokens Used," . $stats['total_tokens_used'] . "\n";
        
        $csv .= "\nDate,Messages\n";
        foreach ($stats['activity_by_day'] as $date => $count) {
            $csv .= $date . "," . $count . "\n";
        }
        
        return $csv;
    }

    private function get_period_start($days) {
        $days = max(1, intval($days));
        return gmdate('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));
    }
}
