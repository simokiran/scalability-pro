<?php

/* Define plugin constants */
if (!defined('SPRO_ATTRIBUTE_AJAX_LIMIT')) {
    //Plugin path
    define('SPRO_ATTRIBUTE_AJAX_LIMIT', 10);
}
if (!defined('SPRO_REMOVE_SORT_ORDER_POST_TYPES')) {
    define('SPRO_REMOVE_SORT_ORDER_POST_TYPES', array('product', array('product', 'product_variation')));
}
if (!defined('SPRO_REDUCE_IMAGE_SIZES')) {
    define('SPRO_REDUCE_IMAGE_SIZES', true);
}
if (!defined('SPRO_CACHE_PMXE_META_KEYS')) {
    define('SPRO_CACHE_PMXE_META_KEYS', false);
}
if (!defined('SPRO_MAX_TRACE_CHARS')) {
    define('SPRO_MAX_TRACE_CHARS', 10000);
}
if (!defined('SPRO_ALWAYS_DO_TERM_RECOUNTS')) { // Always do term counts for these taxonomies - you can add to this array by adding this define to wp-config.php
    define('SPRO_ALWAYS_DO_TERM_RECOUNTS', array('nav_menu', 'link_category', 'post_format', 'wp_theme', 'wp_template_part_area', 'elementor_library_type', 'elementor_library_category'));
}
if (!defined('SPRO_PREVENT_WPAI_DUP_CHECK')) {
    define('SPRO_PREVENT_WPAI_DUP_CHECK', false); // WP All Import runs a duplicate check against a file - this duplicate check is checking for duplicates *within* the file and is wasteful
}
if (!defined('SPRO_KEEP_DEFER_TERM_COUNTS_OPTION')) {
    define('SPRO_KEEP_DEFER_TERM_COUNTS_OPTION', true ); // https://github.com/superspeedyplugins/issue-tracker/issues/49
}
if (!defined('WP_LIST_TABLE_COPY_VERSION')) {
    define('WP_LIST_TABLE_COPY_VERSION', '6.5.2' ); // if I have to update the stored wp-admin/includes/class-wp-list-table etc then also update this version number
}
if (!defined('SPRO_SQL_CALC_ROWS_FINGERPRINT')) {
    define('SPRO_SQL_CALC_ROWS_FINGERPRINT', array()); // we normally only remove SQL_CALC_ROWS from the main query, but if you add stack-trace fingerprints in here, we will remove that from other queries too - fingerprints are text strings which will match sonmething in the stack trace, e.g. the plugin folder or function call which ultimately calls WP_Query
}
if (!defined('SPRO_ALLOW_NAV_MENU_ITEMS_FILTERS')) {
    define('SPRO_ALLOW_NAV_MENU_ITEMS_FILTERS', false); // Query filters tend to get suppressed for nav_menu_items, so our optimisations won't apply to those - you can overrule that by setting this to true
}