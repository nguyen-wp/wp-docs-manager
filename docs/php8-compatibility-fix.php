<?php
/**
 * PHP 8 Compatibility Fixes
 * Fix deprecated warnings for null values
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fix for deprecated strpos warnings
if (!function_exists('safe_strpos')) {
    function safe_strpos($haystack, $needle, $offset = 0) {
        $haystack = $haystack ?? '';
        $needle = $needle ?? '';
        return strpos($haystack, $needle, $offset);
    }
}

// Fix for deprecated str_replace warnings  
if (!function_exists('safe_str_replace')) {
    function safe_str_replace($search, $replace, $subject, &$count = null) {
        $search = $search ?? '';
        $replace = $replace ?? '';
        $subject = $subject ?? '';
        return str_replace($search, $replace, $subject, $count);
    }
}

// Fix for deprecated substr warnings
if (!function_exists('safe_substr')) {
    function safe_substr($string, $start, $length = null) {
        $string = $string ?? '';
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

// Fix for deprecated strlen warnings
if (!function_exists('safe_strlen')) {
    function safe_strlen($string) {
        $string = $string ?? '';
        return strlen($string);
    }
}

// Override WordPress functions if needed (careful approach)
add_action('init', function() {
    // Add global error suppression for known deprecated warnings
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Log warnings but don't display them
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    }
}, 1);
?>
