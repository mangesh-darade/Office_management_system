<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task_model extends CI_Model {
    private $table = 'tasks';
    public function __construct(){ parent::__construct(); $this->load->database(); }
    public function all(){ return $this->db->order_by('id','DESC')->get($this->table)->result(); }
}
