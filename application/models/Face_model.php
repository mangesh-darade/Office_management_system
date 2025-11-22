<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Face_model extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function ensure_schema() {
        if (!$this->db->table_exists('user_faces')) {
            $sql = "CREATE TABLE `user_faces` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `descriptor` longtext NULL,
                `image_path` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_user_faces_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    public function get_by_user($user_id) {
        $this->ensure_schema();
        return $this->db->where('user_id', (int)$user_id)->get('user_faces')->row();
    }

    public function save_user_face($user_id, $descriptor_json, $image_path) {
        $this->ensure_schema();
        $user_id = (int)$user_id;
        $row = $this->get_by_user($user_id);
        $data = array(
            'user_id' => $user_id,
            'descriptor' => $descriptor_json,
            'image_path' => $image_path,
            'created_at' => date('Y-m-d H:i:s'),
        );
        if ($row) {
            unset($data['user_id']);
            $this->db->where('id', (int)$row->id)->update('user_faces', $data);
        } else {
            $this->db->insert('user_faces', $data);
        }
        return true;
    }
}
