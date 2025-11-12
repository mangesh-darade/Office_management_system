<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task_model extends CI_Model {
    private $table = 'tasks';
    public function __construct(){ parent::__construct(); $this->load->database(); }
    public function all(){ return $this->db->order_by('id','DESC')->get($this->table)->result(); }

    // Comments
    public function get_task_comments($task_id){
        $sel = ['c.*', 'u.email'];
        if ($this->db->field_exists('name','users')) { $sel[] = 'u.name'; }
        $this->db->select(implode(', ', $sel))
                 ->from('task_comments c')
                 ->join('users u', 'u.id = c.user_id', 'left')
                 ->where('c.task_id', (int)$task_id)
                 ->order_by('c.created_at','DESC');
        return $this->db->get()->result();
    }

    public function add_comment($task_id, $user_id, $comment){
        $this->db->insert('task_comments', [
            'task_id' => (int)$task_id,
            'user_id' => (int)$user_id,
            'comment' => (string)$comment,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return (int)$this->db->insert_id();
    }

    public function delete_comment($comment_id, $user_id){
        // Allow owner to delete
        $this->db->where(['id' => (int)$comment_id, 'user_id' => (int)$user_id])->delete('task_comments');
        return $this->db->affected_rows() > 0;
    }
}
