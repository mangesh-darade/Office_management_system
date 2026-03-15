<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recruitment_model extends CI_Model {
    private $table_jobs = 'recruitment_job_posts';
    private $table_candidates = 'recruitment_candidates';
    private $table_interviews = 'recruitment_interviews';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        static $done = false;
        if ($done) { return; }
        $done = true;
        // Job Posts
        if (!$this->db->table_exists($this->table_jobs)){
            $sql = "CREATE TABLE `{$this->table_jobs}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `description` text,
                `department` varchar(100) DEFAULT NULL,
                `positions` int(11) DEFAULT 1,
                `experience_level` varchar(50) DEFAULT NULL,
                `status` enum('open','closed','draft') DEFAULT 'draft',
                `created_by` int(11) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        // Candidates
        if (!$this->db->table_exists($this->table_candidates)){
            $sql = "CREATE TABLE `{$this->table_candidates}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `job_post_id` int(11) DEFAULT NULL,
                `first_name` varchar(100) NOT NULL,
                `last_name` varchar(100) DEFAULT NULL,
                `email` varchar(150) NOT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `resume_path` varchar(255) DEFAULT NULL,
                `status` enum('applied','screening','interviewing','offered','hired','rejected') DEFAULT 'applied',
                `source` varchar(100) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_job` (`job_post_id`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        // Interviews
        if (!$this->db->table_exists($this->table_interviews)){
            $sql = "CREATE TABLE `{$this->table_interviews}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `candidate_id` int(11) NOT NULL,
                `interviewer_id` int(11) NOT NULL,
                `interview_date` datetime NOT NULL,
                `type` varchar(50) DEFAULT 'Face to Face',
                `location` varchar(255) DEFAULT NULL,
                `notes` text,
                `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
                `rating` int(11) DEFAULT NULL,
                `feedback` text,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_candidate` (`candidate_id`),
                KEY `idx_interviewer` (`interviewer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    // Job Methods
    public function get_jobs($status = null){
        if ($status) { $this->db->where('status', $status); }
        return $this->db->order_by('created_at', 'DESC')->get($this->table_jobs)->result();
    }

    public function get_job($id){
        return $this->db->get_where($this->table_jobs, ['id' => (int)$id])->row();
    }

    public function save_job($data, $id = null){
        if ($id){
            $this->db->where('id', (int)$id)->update($this->table_jobs, $data);
            return $id;
        }
        $this->db->insert($this->table_jobs, $data);
        return $this->db->insert_id();
    }

    public function delete_job($id){
        $this->db->where('id', (int)$id)->delete($this->table_jobs);
    }

    // Candidate Methods
    public function get_candidates($job_id = null, $status = null){
        $this->db->select('c.*, j.title as job_title');
        $this->db->from($this->table_candidates.' c');
        $this->db->join($this->table_jobs.' j', 'j.id = c.job_post_id', 'left');
        if ($job_id) { $this->db->where('c.job_post_id', (int)$job_id); }
        if ($status) { $this->db->where('c.status', $status); }
        return $this->db->order_by('c.created_at', 'DESC')->get()->result();
    }

    public function get_candidate($id){
        $this->db->select('c.*, j.title as job_title');
        $this->db->from($this->table_candidates.' c');
        $this->db->join($this->table_jobs.' j', 'j.id = c.job_post_id', 'left');
        $this->db->where('c.id', (int)$id);
        return $this->db->get()->row();
    }

    public function save_candidate($data, $id = null){
        if ($id){
            $this->db->where('id', (int)$id)->update($this->table_candidates, $data);
            return $id;
        }
        $this->db->insert($this->table_candidates, $data);
        return $this->db->insert_id();
    }

    // Interview Methods
    public function get_interviews($candidate_id = null){
        $this->db->select('i.*, c.first_name, c.last_name, u.name as interviewer_name');
        $this->db->from($this->table_interviews.' i');
        $this->db->join($this->table_candidates.' c', 'c.id = i.candidate_id');
        $this->db->join('users u', 'u.id = i.interviewer_id', 'left');
        if ($candidate_id) { $this->db->where('i.candidate_id', (int)$candidate_id); }
        return $this->db->order_by('i.interview_date', 'ASC')->get()->result();
    }

    public function save_interview($data){
        $this->db->insert($this->table_interviews, $data);
        return $this->db->insert_id();
    }
}
