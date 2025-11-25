<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reminder_model extends CI_Model {
    public function __construct(){ parent::__construct(); $this->load->database(); }

    public function ensure_schema(){
        if (!$this->db->table_exists('reminders')){
            $sql = "CREATE TABLE `reminders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `from_email` varchar(255) DEFAULT NULL,
                `from_name` varchar(255) DEFAULT NULL,
                `type` varchar(50) DEFAULT NULL,
                `subject` varchar(255) NOT NULL,
                `body` text,
                `send_at` datetime DEFAULT NULL,
                `sent_at` datetime DEFAULT NULL,
                `status` varchar(20) DEFAULT 'queued',
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`),
                KEY `idx_send_at` (`send_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        // Add new columns if missing
        if ($this->db->table_exists('reminders')){
            if (!$this->db->field_exists('from_email','reminders')){
                $this->db->query("ALTER TABLE `reminders` ADD COLUMN `from_email` varchar(255) DEFAULT NULL AFTER `email`");
            }
            if (!$this->db->field_exists('from_name','reminders')){
                $this->db->query("ALTER TABLE `reminders` ADD COLUMN `from_name` varchar(255) DEFAULT NULL AFTER `from_email`");
            }
        }
        if (!$this->db->table_exists('reminder_schedules')){
            $sql2 = "CREATE TABLE `reminder_schedules` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `audience` varchar(20) DEFAULT 'user',
                `user_id` int(11) DEFAULT NULL,
                `weekdays` varchar(50) NOT NULL,
                `schedule_type` varchar(20) DEFAULT 'weekly',
                `send_time` char(5) NOT NULL,
                `one_time_at` datetime DEFAULT NULL,
                `subject` varchar(255) NOT NULL,
                `body` text,
                `active` tinyint(1) DEFAULT 1,
                `last_run_date` date DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql2);
        }
        if ($this->db->table_exists('reminder_schedules')){
            if (!$this->db->field_exists('schedule_type','reminder_schedules')){
                $this->db->query("ALTER TABLE `reminder_schedules` ADD COLUMN `schedule_type` varchar(20) DEFAULT 'weekly' AFTER `weekdays`");
            }
            if (!$this->db->field_exists('one_time_at','reminder_schedules')){
                $this->db->query("ALTER TABLE `reminder_schedules` ADD COLUMN `one_time_at` datetime DEFAULT NULL AFTER `send_time`");
            }
        }
        // Templates table for morning/night
        if (!$this->db->table_exists('reminder_templates')){
            $sql3 = "CREATE TABLE `reminder_templates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(50) NOT NULL,
                `subject` varchar(255) NOT NULL,
                `body` text,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql3);
        }
    }

    public function enqueue($data){
        $row = array(
            'user_id' => isset($data['user_id']) ? (int)$data['user_id'] : null,
            'email' => isset($data['email']) ? $data['email'] : null,
            'from_email' => isset($data['from_email']) ? $data['from_email'] : null,
            'from_name' => isset($data['from_name']) ? $data['from_name'] : null,
            'type' => isset($data['type']) ? $data['type'] : null,
            'subject' => isset($data['subject']) ? $data['subject'] : '',
            'body' => isset($data['body']) ? $data['body'] : '',
            'send_at' => isset($data['send_at']) ? $data['send_at'] : date('Y-m-d H:i:s'),
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('reminders', $row);
        return (int)$this->db->insert_id();
    }

    public function delete($id){
        $this->db->where('id', (int)$id)->delete('reminders');
        return $this->db->affected_rows();
    }

    public function delete_bulk($ids){
        if (!is_array($ids) || empty($ids)) return 0;
        $clean = array();
        foreach ($ids as $i){ $clean[] = (int)$i; }
        if (empty($clean)) return 0;
        $this->db->where_in('id', $clean)->delete('reminders');
        return $this->db->affected_rows();
    }

    public function list_recent($limit = 100){
        if ($this->db->table_exists('users')){
            $labelSql = array();
            if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $labelSql[] = "CONCAT(u.first_name,' ',u.last_name) AS full_label"; }
            if ($this->db->field_exists('full_name','users')){ $labelSql[] = 'u.full_name'; }
            if ($this->db->field_exists('name','users')){ $labelSql[] = 'u.name'; }
            $select = 'r.*, u.email AS user_email';
            if (!empty($labelSql)){ $select .= ','.implode(',', $labelSql); }
            return $this->db->select($select, false)
                ->from('reminders r')
                ->join('users u','u.id = r.user_id','left')
                ->order_by('r.id','DESC')
                ->limit((int)$limit)
                ->get()->result();
        }
        return $this->db->order_by('id','DESC')->limit((int)$limit)->get('reminders')->result();
    }

    public function fetch_queue($limit = 50){
        $now = date('Y-m-d H:i:s');
        return $this->db->where('status','queued')->where('send_at <=', $now)->order_by('id','ASC')->limit((int)$limit)->get('reminders')->result();
    }

    public function mark_sent($id){
        $this->db->where('id',(int)$id)->update('reminders', array('status'=>'sent','sent_at'=>date('Y-m-d H:i:s')));
        return $this->db->affected_rows();
    }

    public function mark_error($id){
        return $this->db->where('id',(int)$id)->update('reminders', array('status'=>'error'));
    }

    public function all_users(){
        $labelSql = array();
        if ($this->db->field_exists('first_name','users') && $this->db->field_exists('last_name','users')){ $labelSql[] = "CONCAT(first_name,' ',last_name) AS full_label"; }
        if ($this->db->field_exists('full_name','users')){ $labelSql[] = 'full_name'; }
        if ($this->db->field_exists('name','users')){ $labelSql[] = 'name'; }
        $select = 'id,email';
        if (!empty($labelSql)){ $select .= ','.implode(',', $labelSql); }
        return $this->db->select($select, false)->from('users')->where('status !=','inactive')->get()->result();
    }

    // Templates helpers
    public function get_template($code){
        $row = $this->db->get_where('reminder_templates', array('code'=>$code))->row();
        if ($row) { return $row; }
        return null;
    }

    public function save_template($code, $subject, $body){
        $exists = $this->db->get_where('reminder_templates', array('code'=>$code))->row();
        $data = array('subject'=>$subject, 'body'=>$body, 'updated_at'=>date('Y-m-d H:i:s'));
        if ($exists){
            $this->db->where('id', (int)$exists->id)->update('reminder_templates', $data);
            return (int)$exists->id;
        } else {
            $data['code'] = $code;
            $this->db->insert('reminder_templates', $data);
            return (int)$this->db->insert_id();
        }
    }

    public function render_template($subject, $body, $vars){
        foreach ($vars as $k=>$v){
            $subject = str_replace('{'.$k.'}', $v, $subject);
            $body = str_replace('{'.$k.'}', $v, $body);
        }
        return array($subject, $body);
    }

    // Schedules
    public function list_schedules(){
        if (!$this->db->table_exists('reminder_schedules')){ return array(); }
        return $this->db->order_by('id','DESC')->get('reminder_schedules')->result();
    }

    public function create_schedule($data){
        $this->db->insert('reminder_schedules', $data);
        return (int)$this->db->insert_id();
    }

    public function get_schedule($id){
        if (!$this->db->table_exists('reminder_schedules')){ return null; }
        return $this->db->get_where('reminder_schedules', array('id' => (int)$id))->row();
    }

    public function update_schedule($id, $data){
        if (!$this->db->table_exists('reminder_schedules')){ return 0; }
        $this->db->where('id', (int)$id)->update('reminder_schedules', $data);
        return $this->db->affected_rows();
    }

    public function delete_schedule($id){
        if (!$this->db->table_exists('reminder_schedules')){ return 0; }
        $this->db->where('id', (int)$id)->delete('reminder_schedules');
        return $this->db->affected_rows();
    }

    public function set_schedule_active($id, $active){
        if (!$this->db->table_exists('reminder_schedules')){ return 0; }
        $this->db->where('id', (int)$id)->update('reminder_schedules', array('active' => $active ? 1 : 0));
        return $this->db->affected_rows();
    }

    public function fetch_due_schedules($weekday, $time){
        if (!$this->db->table_exists('reminder_schedules')){ return array(); }
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        // Weekly schedules: weekday match and time <= now and not yet run today
        $this->db->from('reminder_schedules');
        $this->db->where('active', 1);
        // Treat NULL or 'weekly' as weekly type
        $this->db->group_start();
        $this->db->where('schedule_type', 'weekly');
        $this->db->or_where('schedule_type IS NULL', null, false);
        $this->db->group_end();
        $this->db->like('weekdays', (string)$weekday); // CSV contains weekday number
        $this->db->where('send_time <=', $time);
        $this->db->group_start();
        $this->db->where('last_run_date IS NULL', null, false);
        $this->db->or_where('last_run_date <', $today);
        $this->db->group_end();
        $weekly = $this->db->get()->result();

        // One-time schedules: fire once at or before one_time_at, never run before
        $this->db->from('reminder_schedules');
        $this->db->where('active', 1);
        $this->db->where('schedule_type', 'once');
        $this->db->where('one_time_at <=', $now);
        $this->db->where('last_run_date IS NULL', null, false);
        $once = $this->db->get()->result();

        return array_merge($weekly, $once);
    }

    public function mark_schedule_ran_today($id){
        return $this->db->where('id',(int)$id)->update('reminder_schedules', array('last_run_date'=>date('Y-m-d')));
    }
}
