<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eat_Cookies_Database {
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eat_cookies_consents';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            consent_id varchar(50) NOT NULL,
            user_ip varchar(100) DEFAULT '',
            user_agent text DEFAULT '',
            consent_state text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function save_consent($consent_id, $consent_state) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eat_cookies_consents';

        $wpdb->insert(
            $table_name,
            [
                'consent_id'    => $consent_id,
                'user_ip'       => $_SERVER['REMOTE_ADDR'],
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
                'consent_state' => json_encode($consent_state),
                'timestamp'     => current_time('mysql'),
            ]
        );
    }
}
