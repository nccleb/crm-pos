<?php
/**
 * ajax/pos_log.php
 * Shared activity logging helper for NCC CRM POS
 * Include this file in any AJAX backend that needs to log actions.
 *
 * Usage:
 *   require_once __DIR__ . '/pos_log.php';
 *   posLog($conn, $agent_id, $agent_name, 'sale_completed', 'Sale #123 — LL 450,000', 123, 'sale');
 */

if (!function_exists('posLog')) {
    function posLog($conn, $agent_id, $agent_name, $action, $details = '', $ref_id = null, $ref_type = null) {
        $agent_id   = (int)$agent_id;
        $agent_name = mysqli_real_escape_string($conn, (string)$agent_name);
        $action     = mysqli_real_escape_string($conn, (string)$action);
        $details    = mysqli_real_escape_string($conn, (string)$details);
        $ref_id_sql = $ref_id  ? (int)$ref_id                                                    : 'NULL';
        $ref_type   = $ref_type ? "'" . mysqli_real_escape_string($conn, $ref_type) . "'" : 'NULL';
        $ip         = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');

        mysqli_query($conn,
            "INSERT INTO pos_activity_log
                (agent_id, agent_name, action, details, reference_id, reference_type, ip_address)
             VALUES
                ($agent_id, '$agent_name', '$action', '$details', $ref_id_sql, $ref_type, '$ip')"
        );
    }
}
