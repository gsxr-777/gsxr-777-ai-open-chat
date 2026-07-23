<?php
/**
 * Provide a admin area view for the Knowledge Base Settings
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wln.su/
 * @since      1.0.0
 *
 * @package    Gsxr_777
 * @subpackage Gsxr_777/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div id="gsxr777-knowledge-manager">
        <div class="knowledge-toolbar">
            <button type="button" id="gsxr777-add-file" class="button button-primary"><?php esc_html_e('Add New File', 'gsxr-777-ai-open-chat'); ?></button>
            <input type="file" id="gsxr777-upload-file" accept=".md" hidden>
            <button type="button" id="gsxr777-upload-btn" class="button"><?php esc_html_e('Upload File', 'gsxr-777-ai-open-chat'); ?></button>
        </div>
        <div id="gsxr777-file-list">
            <!-- File list will be populated via AJAX -->
        </div>
        <div id="gsxr777-file-editor" hidden>
            <!-- Editor will be populated when file is selected -->
        </div>
    </div>
</div>
