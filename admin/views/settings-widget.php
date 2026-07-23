<?php
/**
 * Provide a admin area view for the Widget Settings
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
    <form method="post" action="options.php">
        <?php
        settings_fields('gsxr_777_widget_settings');
        do_settings_sections('gsxr_777_widget_settings');
        submit_button();
        ?>
    </form>
</div>
