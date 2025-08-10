<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_model extends CI_Model {
    private $table = 'attendance';
    public function __construct(){ parent::__construct(); $this->load->database(); }
}
