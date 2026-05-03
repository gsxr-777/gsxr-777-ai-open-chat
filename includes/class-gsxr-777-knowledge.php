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
        if (!file_exists($this->knowledge_dir)) {
            return array();
        }

        $files = array();
        try {
            $iterator = new DirectoryIterator($this->knowledge_dir);

            foreach ($iterator as $file) {
                if ($file->isDot() || !$file->isFile()) {
                    continue;
                }

                $filename = $file->getFilename();
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'md') {
                    $files[] = array(
                        'name' => $filename,
                        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                        'size' => $file->getSize()
                    );
                }
            }
        } catch (Exception $e) {
            error_log('GSXR-777 Knowledge Error: ' . $e->getMessage());
            return array();
        }

        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });

        return $files;
    }

    public function get_file_content($filename) {
        if (!$this->validate_filename($filename)) {
            return false;
        }

        $filepath = $this->knowledge_dir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }

        return file_get_contents($filepath);
    }

    public function save_file($filename, $content) {
        if (!$this->validate_filename($filename)) {
            return false;
        }

        // Ensure knowledge directory exists
        if (!file_exists($this->knowledge_dir)) {
            wp_mkdir_p($this->knowledge_dir);
        }

        $filepath = $this->knowledge_dir . '/' . $filename;
        
        // Validate content encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            return false;
        }
        
        // Validate content for dangerous patterns
        if (!$this->validate_markdown_content($content)) {
            return false;
        }
        
        // Sanitize markdown content (remove dangerous HTML)
        $content = $this->sanitize_markdown_content($content);

        // Save file
        $result = file_put_contents($filepath, $content, LOCK_EX);
        
        return $result !== false;
    }

    private function validate_markdown_content($content) {
        // Check for dangerous patterns that could cause XSS
        $dangerous_patterns = array(
            '/<script[^>]*>/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',  // onerror, onclick, etc.
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }

    private function sanitize_markdown_content($content) {
        // Remove dangerous HTML tags if present
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $content);
        $content = preg_replace('/<object[^>]*>.*?<\/object>/is', '', $content);
        $content = preg_replace('/<embed[^>]*>.*?<\/embed>/is', '', $content);
        
        // Remove event handlers from remaining HTML
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        return $content;
    }

    public function delete_file($filename) {
        if (!$this->validate_filename($filename)) {
            return false;
        }

        $filepath = $this->knowledge_dir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }

        return unlink($filepath);
    }

    public function upload_file($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array(
                'success' => false,
                'error' => __('Invalid file upload', 'gsxr-777')
            );
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return array(
                'success' => false,
                'error' => __('File size exceeds 5MB limit', 'gsxr-777')
            );
        }

        // Check file extension
        $filename = sanitize_file_name($file['name']);
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'md') {
            return array(
                'success' => false,
                'error' => __('Only .md files are allowed', 'gsxr-777')
            );
        }

        // Read and validate content
        $content = file_get_contents($file['tmp_name']);
        if (!mb_check_encoding($content, 'UTF-8')) {
            return array(
                'success' => false,
                'error' => __('File must be UTF-8 encoded', 'gsxr-777')
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
                'error' => __('Failed to save file', 'gsxr-777')
            );
        }
    }

    public function get_aggregated_content() {
        $files = $this->get_all_files();
        $content = '';

        foreach ($files as $file) {
            // Check file exists before reading (prevent race condition)
            if (!file_exists($this->knowledge_dir . '/' . $file['name'])) {
                continue;
            }
            
            $file_content = $this->get_file_content($file['name']);
            if ($file_content !== false) {
                $content .= "\n\n--- " . $file['name'] . " ---\n";
                $content .= $file_content;
            }
        }

        return trim($content);
    }

    private function validate_filename($filename) {
        // Check if filename is valid
        if (empty($filename) || strlen($filename) > 255) {
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
            if (!file_exists($this->knowledge_dir . '/' . $filename)) {
                $this->save_file($filename, $content);
            }
        }
    }
}