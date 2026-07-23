<?php
/**
 * Knowledge Base Manager class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSXR_777_Knowledge {

    private $knowledge_dir;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->knowledge_dir = $upload_dir['basedir'] . '/gsxr-777-knowledge';
    }

    public function get_all_files() {
        global $wpdb;

        // Table names are identifiers, not value placeholders. Escape the
        // trusted WordPress prefix before interpolating it into SQL.
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_documents');
        $results = $wpdb->get_results(
            "SELECT filename, updated_at, CHAR_LENGTH(content) AS content_size
             FROM {$table_name}
             ORDER BY updated_at DESC"
        );

        if (!is_array($results)) {
            return array();
        }

        return array_map(function($row) {
            return array(
                'name' => $row->filename,
                'modified' => $row->updated_at,
                'size' => intval($row->content_size)
            );
        }, $results);
    }

    public function get_file_content($filename) {
        global $wpdb;

        if (!$this->validate_filename($filename)) {
            return false;
        }

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_documents');
        $content = $wpdb->get_var($wpdb->prepare(
            "SELECT content FROM {$table_name} WHERE filename = %s",
            $filename
        ));

        if (is_string($content)) {
            return $content;
        }

        // Read-only legacy fallback until the migration runs.
        $legacy_path = $this->knowledge_dir . '/' . $filename;
        return is_file($legacy_path) ? file_get_contents($legacy_path) : false;
    }

    public function save_file($filename, $content) {
        global $wpdb;

        if (!$this->validate_filename($filename)) {
            return false;
        }

        $max_bytes = max(
            1024,
            intval(apply_filters('gsxr_777_knowledge_file_max_bytes', 1024 * 1024))
        );
        if (!is_string($content) || strlen($content) > $max_bytes) {
            return false;
        }

        // Validate content encoding without requiring the mbstring extension.
        $content = wp_check_invalid_utf8($content, true);
        if ($content === '') {
            return false;
        }
        
        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_documents');
        $source_key = $this->create_source_key('file', $filename);
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table_name}
                (source_key, filename, content, content_hash, updated_at)
             VALUES (%s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
                content = VALUES(content),
                content_hash = VALUES(content_hash),
                updated_at = VALUES(updated_at)",
            $source_key,
            $filename,
            $content,
            hash('sha256', $content),
            current_time('mysql')
        ));
        if ($result === false) {
            return false;
        }

        $this->index_file($filename);
        return true;
    }

    public function delete_file($filename) {
        global $wpdb;

        if (!$this->validate_filename($filename)) {
            return false;
        }

        $table_name = $wpdb->prefix . 'gsxr777_knowledge_documents';
        $deleted = $wpdb->delete($table_name, array('filename' => $filename), array('%s'));
        if ($deleted) {
            $this->delete_source_from_index('file', $filename);
        }

        $legacy_path = $this->knowledge_dir . '/' . $filename;
        if (is_file($legacy_path)) {
            wp_delete_file($legacy_path);
        }

        return $deleted !== false;
    }

    public function upload_file($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array(
                'success' => false,
                'error' => __('Invalid file upload', 'gsxr-777-ai-open-chat')
            );
        }

        $max_bytes = max(
            1024,
            intval(apply_filters('gsxr_777_knowledge_file_max_bytes', 1024 * 1024))
        );

        if ($file['size'] > $max_bytes) {
            return array(
                'success' => false,
                'error' => __('Knowledge file is too large', 'gsxr-777-ai-open-chat')
            );
        }

        // Check file extension
        $filename = sanitize_file_name($file['name']);
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
            return array(
                'success' => false,
                'error' => __('Only .md files are allowed', 'gsxr-777-ai-open-chat')
            );
        }

        // Read and validate content
        $content = file_get_contents($file['tmp_name']);
        if (!is_string($content) || wp_check_invalid_utf8($content, true) === '') {
            return array(
                'success' => false,
                'error' => __('File must be UTF-8 encoded', 'gsxr-777-ai-open-chat')
            );
        }

        // Save file
        if ($this->save_file($filename, $content)) {
            return array(
                'success' => true,
                'filename' => $filename
            );
        } else {
            return array(
                'success' => false,
                'error' => __('Failed to save file', 'gsxr-777-ai-open-chat')
            );
        }
    }

    public function get_aggregated_content() {
        $files = $this->get_all_files();
        $content = '';

        foreach ($files as $file) {
            $file_content = $this->get_file_content($file['name']);
            if ($file_content !== false) {
                $content .= "\n\n--- " . $file['name'] . " ---\n";
                $content .= $file_content;
            }
        }

        return trim($content);
    }

    public function retrieve_relevant_content($query, $limit = 6, $max_characters = 8000) {
        global $wpdb;

        $query = trim(wp_strip_all_tags((string) $query));
        if ($query === '') {
            return '';
        }

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        $limit = max(1, min(12, intval($limit)));
        $max_characters = max(1000, min(20000, intval($max_characters)));
        $results = array();

        $previous_suppress_errors = $wpdb->suppress_errors(true);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT source_url, title, content,
                    MATCH(title, content) AGAINST (%s IN NATURAL LANGUAGE MODE) AS relevance
             FROM {$table_name}
             WHERE MATCH(title, content) AGAINST (%s IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC, updated_at DESC
             LIMIT %d",
            $query,
            $query,
            $limit
        ));
        $wpdb->suppress_errors($previous_suppress_errors);

        if (empty($results)) {
            $results = $this->fallback_keyword_search($query, $limit);
        }

        $selected = '';
        foreach ($results as $result) {
            $source_label = trim((string) $result->title);
            if (!empty($result->source_url)) {
                $source_label .= ' — ' . esc_url_raw($result->source_url);
            }

            $chunk = sprintf(
                "[Source: %s]\n%s",
                $source_label,
                trim((string) $result->content)
            );

            $remaining = $max_characters - $this->text_length($selected);
            if ($remaining <= 0) {
                break;
            }

            if ($this->text_length($chunk) > $remaining) {
                $chunk = $this->text_substr($chunk, 0, $remaining);
            }

            $selected .= ($selected === '' ? '' : "\n\n") . $chunk;
        }

        return trim($selected);
    }

    public function index_file($filename) {
        if (!$this->validate_filename($filename)) {
            return false;
        }

        $content = $this->get_file_content($filename);
        if ($content === false) {
            return false;
        }

        return $this->replace_source_chunks(
            'file',
            $filename,
            '',
            $filename,
            $this->chunk_text($content)
        );
    }

    public function index_post($post_id, $post = null, $update = false) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $post = $post instanceof WP_Post ? $post : get_post($post_id);
        if (!$post) {
            return;
        }

        $public_post_types = get_post_types(array('public' => true));
        if ($post->post_status !== 'publish' || !in_array($post->post_type, $public_post_types, true) || $post->post_type === 'attachment') {
            $this->delete_source_from_index('post', (string) $post_id);
            return;
        }

        $content = strip_shortcodes($post->post_content);
        $content = html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES, 'UTF-8');
        $content = trim($post->post_title . "\n\n" . $content);

        $this->replace_source_chunks(
            'post',
            (string) $post_id,
            get_permalink($post_id),
            get_the_title($post_id),
            $this->chunk_text($content)
        );
    }

    public function delete_post_from_index($post_id) {
        $this->delete_source_from_index('post', (string) $post_id);
    }

    public function rebuild_index() {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        $this->migrate_legacy_files();

        foreach ($this->get_all_files() as $file) {
            $this->index_file($file['name']);
        }

        $post_types = array_values(array_diff(
            get_post_types(array('public' => true)),
            array('attachment')
        ));
        if (empty($post_types)) {
            return;
        }

        $page = 1;
        do {
            $query = new WP_Query(array(
                'post_type' => $post_types,
                'post_status' => 'publish',
                'posts_per_page' => 100,
                'paged' => $page,
                'orderby' => 'ID',
                'order' => 'ASC',
                'fields' => 'ids',
                'no_found_rows' => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false
            ));

            foreach ($query->posts as $post_id) {
                $this->index_post($post_id);
            }

            $page++;
        } while ($page <= intval($query->max_num_pages));
    }

    public function get_index_stats() {
        global $wpdb;

        $chunks_table = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        return array(
            'chunks' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$chunks_table}")),
            'sources' => intval($wpdb->get_var("SELECT COUNT(DISTINCT source_key) FROM {$chunks_table}"))
        );
    }

    private function replace_source_chunks($source_type, $source_identifier, $source_url, $title, $chunks) {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        $source_key = $this->create_source_key($source_type, $source_identifier);
        $wpdb->query('START TRANSACTION');

        $deleted = $wpdb->delete(
            $table_name,
            array('source_type' => $source_type, 'source_key' => $source_key),
            array('%s', '%s')
        );
        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($chunks as $index => $chunk) {
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'source_type' => sanitize_key($source_type),
                    'source_key' => $source_key,
                    'source_url' => esc_url_raw($source_url),
                    'title' => sanitize_text_field($title),
                    'chunk_index' => intval($index),
                    'content' => $chunk,
                    'content_hash' => hash('sha256', $chunk),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );

            if ($inserted === false) {
                $wpdb->query('ROLLBACK');
                error_log('GSXR-777: Could not update the knowledge index');
                return false;
            }
        }

        $wpdb->query('COMMIT');
        return true;
    }

    private function delete_source_from_index($source_type, $source_identifier) {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . 'gsxr777_knowledge_chunks');
        return $wpdb->delete(
            $table_name,
            array(
                'source_type' => sanitize_key($source_type),
                'source_key' => $this->create_source_key($source_type, $source_identifier)
            ),
            array('%s', '%s')
        );
    }

    private function create_source_key($source_type, $source_identifier) {
        return hash('sha256', sanitize_key($source_type) . '|' . (string) $source_identifier);
    }

    private function chunk_text($content, $chunk_size = 1400, $overlap = 180) {
        $content = wp_check_invalid_utf8(wp_strip_all_tags((string) $content), true);
        $content = preg_replace('/[ \t]+/u', ' ', $content);
        $content = preg_replace('/\n{3,}/u', "\n\n", $content);
        $content = trim($content);
        if ($content === '') {
            return array();
        }

        $chunk_size = max(500, intval($chunk_size));
        $overlap = max(0, min(intval($overlap), $chunk_size - 100));
        $length = $this->text_length($content);
        $chunks = array();
        $offset = 0;

        while ($offset < $length) {
            $chunk = trim($this->text_substr($content, $offset, $chunk_size));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            $offset += max(1, $chunk_size - $overlap);
        }

        return $chunks;
    }

    private function fallback_keyword_search($query, $limit) {
        global $wpdb;

        $terms = preg_split('/[^\p{L}\p{N}_-]+/u', $this->text_lower($query), -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_values(array_unique(array_filter($terms, function($term) {
            return $this->text_length($term) >= 3;
        })));
        $terms = array_slice($terms, 0, 8);
        if (empty($terms)) {
            return array();
        }

        $table_name = $wpdb->prefix . 'gsxr777_knowledge_chunks';
        $score_parts = array();
        $where_parts = array();
        $score_params = array();
        $where_params = array();

        foreach ($terms as $term) {
            $like = '%' . $wpdb->esc_like($term) . '%';
            $score_parts[] = '(CASE WHEN LOWER(title) LIKE %s THEN 3 ELSE 0 END + CASE WHEN LOWER(content) LIKE %s THEN 1 ELSE 0 END)';
            $where_parts[] = '(LOWER(title) LIKE %s OR LOWER(content) LIKE %s)';
            array_push($score_params, $like, $like);
            array_push($where_params, $like, $like);
        }

        $sql = "SELECT source_url, title, content, (" . implode(' + ', $score_parts) . ") AS relevance
                FROM {$table_name}
                WHERE " . implode(' OR ', $where_parts) . "
                ORDER BY relevance DESC, updated_at DESC
                LIMIT %d";
        $params = array_merge($score_params, $where_params, array($limit));
        // The SQL contains a validated identifier and dynamically generated
        // LIKE placeholders; prepare() receives all values below.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared immediately with wpdb::prepare().
        $prepared = $wpdb->prepare($sql, ...$params);

        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- $prepared is the result of wpdb::prepare().
        return $wpdb->get_results($prepared);
    }

    private function text_length($text) {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8');
        }
        if (function_exists('iconv_strlen')) {
            $length = iconv_strlen($text, 'UTF-8');
            if ($length !== false) {
                return $length;
            }
        }

        $matched = preg_match_all('/./us', $text, $characters);
        return $matched === false ? strlen($text) : $matched;
    }

    private function text_substr($text, $start, $length) {
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start, $length, 'UTF-8');
        }
        if (function_exists('iconv_substr')) {
            $substring = iconv_substr($text, $start, $length, 'UTF-8');
            if ($substring !== false) {
                return $substring;
            }
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($characters)
            ? implode('', array_slice($characters, $start, $length))
            : substr($text, $start, $length);
    }

    private function text_lower($text) {
        return function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower($text);
    }

    private function validate_filename($filename) {
        // Check if filename is valid
        if (empty($filename) || strlen($filename) > 190) {
            return false;
        }

        // Check for directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return false;
        }

        // Check file extension
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
            return false;
        }

        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9._-]+\.md$/', $filename)) {
            return false;
        }

        return true;
    }

    public function get_knowledge_directory() {
        return $this->knowledge_dir;
    }

    public function create_example_files() {
        $examples = array(
            'welcome.md' => "# Welcome to GSXR-777 AI Chat\n\nThis is your AI chat assistant. I can help you with questions about your website and provide support to your visitors.\n\n## Features\n\n- Answer questions about your products and services\n- Provide customer support\n- Help with navigation\n- Share information from your knowledge base",
            
            'faq.md' => "# Frequently Asked Questions\n\n## How does the AI chat work?\n\nThe AI chat uses advanced language models to understand and respond to user questions based on your website content and knowledge base.\n\n## Can I customize the chat widget?\n\nYes! You can customize colors, position, messages, and more in the Widget Settings page.\n\n## Is my data secure?\n\nYes, all conversations are processed securely and you have full control over your data.",
            
            'contact.md' => "# Contact Information\n\n## Support Hours\nMonday - Friday: 9:00 AM - 6:00 PM\nSaturday: 10:00 AM - 4:00 PM\nSunday: Closed\n\n## Contact Methods\n- Email: support@example.com\n- Phone: +1 (555) 123-4567\n- Live Chat: Available during business hours"
        );

        foreach ($examples as $filename => $content) {
            if ($this->get_file_content($filename) === false) {
                $this->save_file($filename, $content);
            }
        }
    }

    public function migrate_legacy_files() {
        if (get_option('gsxr_777_legacy_knowledge_migrated', false)) {
            return;
        }

        if (!is_dir($this->knowledge_dir)) {
            update_option('gsxr_777_legacy_knowledge_migrated', 1, false);
            return;
        }

        $migration_complete = true;
        try {
            $iterator = new DirectoryIterator($this->knowledge_dir);
            foreach ($iterator as $file) {
                if ($file->isDot() || !$file->isFile()) {
                    continue;
                }

                $filename = $file->getFilename();
                if (!$this->validate_filename($filename)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                if (is_string($content) && $this->save_file($filename, $content)) {
                    wp_delete_file($file->getPathname());
                    if (is_file($file->getPathname())) {
                        $migration_complete = false;
                    }
                } else {
                    $migration_complete = false;
                }
            }
        } catch (Exception $exception) {
            $migration_complete = false;
            error_log('GSXR-777: Legacy knowledge migration failed');
        }

        if ($migration_complete) {
            update_option('gsxr_777_legacy_knowledge_migrated', 1, false);
        }
    }
}
