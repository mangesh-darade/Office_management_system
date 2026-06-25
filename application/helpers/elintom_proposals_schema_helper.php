<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('elintom_proposals_schema_ensure')) {
    function elintom_proposals_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('elintom_proposals')) {
            $sql = "CREATE TABLE `elintom_proposals` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `client_name` varchar(255) NOT NULL DEFAULT '',
                `client_business` varchar(255) NOT NULL DEFAULT '',
                `document_path` varchar(500) DEFAULT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_elintom_proposals_created_by` (`created_by`),
                KEY `idx_elintom_proposals_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
    }
}
