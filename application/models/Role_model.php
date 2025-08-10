<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {
    private $table = 'roles';
    public function __construct(){ parent::__construct(); $this->load->database(); }
    public function get($id){ return $this->db->get_where($this->table, ['id' => (int)$id])->row(); }
}
