<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends CI_Model {
    private $table_struct = 'salary_structures';
    private $table_payslips = 'payslips';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        // Salary structures
        if (!$this->db->table_exists($this->table_struct)){
            $sql = "CREATE TABLE `{$this->table_struct}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `basic` decimal(10,2) NOT NULL DEFAULT 0,
                `hra` decimal(10,2) NOT NULL DEFAULT 0,
                `conveyance_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `medical_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `education_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `special_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `professional_tax` decimal(10,2) NOT NULL DEFAULT 0,
                `tds` decimal(10,2) NOT NULL DEFAULT 0,
                `allowances` decimal(10,2) NOT NULL DEFAULT 0,
                `deductions` decimal(10,2) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_salary_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        } else {
            // Ensure new component columns exist on older installs
            $fields = $this->db->list_fields($this->table_struct);
            $addCol = function($name, $sqlPart) use ($fields){
                if (!in_array($name, $fields, true)){
                    $this->db->query("ALTER TABLE `{$this->table_struct}` ADD ".$sqlPart);
                }
            };
            $addCol('conveyance_allow', "`conveyance_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `hra`");
            $addCol('medical_allow', "`medical_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `conveyance_allow`");
            $addCol('education_allow', "`education_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `medical_allow`");
            $addCol('special_allow', "`special_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `education_allow`");
            $addCol('professional_tax', "`professional_tax` decimal(10,2) NOT NULL DEFAULT 0 AFTER `special_allow`");
            $addCol('tds', "`tds` decimal(10,2) NOT NULL DEFAULT 0 AFTER `professional_tax`");
        }
        // Payslips
        if (!$this->db->table_exists($this->table_payslips)){
            $sql2 = "CREATE TABLE `{$this->table_payslips}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `period` varchar(7) NOT NULL,
                `basic` decimal(10,2) NOT NULL DEFAULT 0,
                `hra` decimal(10,2) NOT NULL DEFAULT 0,
                `conveyance_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `medical_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `education_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `special_allow` decimal(10,2) NOT NULL DEFAULT 0,
                `professional_tax` decimal(10,2) NOT NULL DEFAULT 0,
                `tds` decimal(10,2) NOT NULL DEFAULT 0,
                `allowances` decimal(10,2) NOT NULL DEFAULT 0,
                `deductions` decimal(10,2) NOT NULL DEFAULT 0,
                `gross` decimal(10,2) NOT NULL DEFAULT 0,
                `net` decimal(10,2) NOT NULL DEFAULT 0,
                `pay_mode` varchar(50) DEFAULT NULL,
                `bank_name` varchar(190) DEFAULT NULL,
                `bank_ac_no` varchar(50) DEFAULT NULL,
                `pan_no` varchar(20) DEFAULT NULL,
                `location` varchar(100) DEFAULT NULL,
                `payment_days` decimal(6,2) DEFAULT NULL,
                `present_days` decimal(6,2) DEFAULT NULL,
                `paid_leaves` decimal(6,2) DEFAULT NULL,
                `leave_without_pay` decimal(6,2) DEFAULT NULL,
                `balance_leaves` decimal(6,2) DEFAULT NULL,
                `remarks` text,
                `generated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_payslip_user_period` (`user_id`,`period`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql2);
        } else {
            // Ensure new optional columns exist
            $fields = $this->db->list_fields($this->table_payslips);
            $addCol = function($name, $sqlPart) use ($fields){
                if (!in_array($name, $fields, true)){
                    $this->db->query("ALTER TABLE `{$this->table_payslips}` ADD ".$sqlPart);
                }
            };
            $addCol('pay_mode', "`pay_mode` varchar(50) DEFAULT NULL AFTER `net`");
            $addCol('bank_name', "`bank_name` varchar(190) DEFAULT NULL AFTER `pay_mode`");
            $addCol('bank_ac_no', "`bank_ac_no` varchar(50) DEFAULT NULL AFTER `bank_name`");
            $addCol('pan_no', "`pan_no` varchar(20) DEFAULT NULL AFTER `bank_ac_no`");
            $addCol('location', "`location` varchar(100) DEFAULT NULL AFTER `pan_no`");
            $addCol('conveyance_allow', "`conveyance_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `hra`");
            $addCol('medical_allow', "`medical_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `conveyance_allow`");
            $addCol('education_allow', "`education_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `medical_allow`");
            $addCol('special_allow', "`special_allow` decimal(10,2) NOT NULL DEFAULT 0 AFTER `education_allow`");
            $addCol('professional_tax', "`professional_tax` decimal(10,2) NOT NULL DEFAULT 0 AFTER `special_allow`");
            $addCol('tds', "`tds` decimal(10,2) NOT NULL DEFAULT 0 AFTER `professional_tax`");
            $addCol('payment_days', "`payment_days` decimal(6,2) DEFAULT NULL AFTER `location`");
            $addCol('present_days', "`present_days` decimal(6,2) DEFAULT NULL AFTER `payment_days`");
            $addCol('paid_leaves', "`paid_leaves` decimal(6,2) DEFAULT NULL AFTER `present_days`");
            $addCol('leave_without_pay', "`leave_without_pay` decimal(6,2) DEFAULT NULL AFTER `paid_leaves`");
            $addCol('balance_leaves', "`balance_leaves` decimal(6,2) DEFAULT NULL AFTER `leave_without_pay`");
        }
    }

    public function get_user_options(){
        $opts = [];
        if (!$this->db->table_exists('users')) { return $opts; }
        $this->db->select('id, email, name');
        $rows = $this->db->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        foreach ($rows as $r){
            $label = $r->email;
            if (!empty($r->name)) { $label = $r->name.' <'.$r->email.'>'; }
            $opts[] = ['id' => (int)$r->id, 'label' => $label];
        }
        return $opts;
    }

    public function get_structures(){
        $this->db->select('s.*, u.email, u.name');
        $this->db->from($this->table_struct.' s');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        $this->db->order_by('u.email','ASC');
        return $this->db->get()->result();
    }

    public function get_structure($user_id){
        return $this->db->get_where($this->table_struct, ['user_id' => (int)$user_id])->row();
    }

    public function save_structure($user_id, $data){
        $user_id = (int)$user_id;
        $existing = $this->get_structure($user_id);
        $now = date('Y-m-d H:i:s');
        $data['updated_at'] = $now;
        if ($existing){
            $this->db->where('user_id', $user_id)->update($this->table_struct, $data);
            return $existing->id;
        }
        $data['user_id'] = $user_id;
        $data['created_at'] = $now;
        $this->db->insert($this->table_struct, $data);
        return (int)$this->db->insert_id();
    }

    public function list_payslips($filters = []){
        $this->db->select('p.*, u.email, u.name');
        $this->db->from($this->table_payslips.' p');
        $this->db->join('users u','u.id = p.user_id','left');
        if (!empty($filters['period'])){
            $this->db->where('p.period', $filters['period']);
        }
        if (!empty($filters['user_id'])){
            $this->db->where('p.user_id', (int)$filters['user_id']);
        }
        $this->db->order_by('p.period','DESC');
        $this->db->order_by('u.email','ASC');
        return $this->db->get()->result();
    }

    public function find_payslip($id){
        $this->db->select('p.*, u.email, u.name');
        $this->db->from($this->table_payslips.' p');
        $this->db->join('users u','u.id = p.user_id','left');
        // Optional employee details when employees table exists
        if ($this->db->table_exists('employees')) {
            $this->db->select('e.emp_code, e.first_name, e.last_name, e.department, e.designation, e.join_date, e.address, e.city, e.state, e.country, e.zipcode');
            $this->db->join('employees e','e.user_id = u.id','left');
        }
        $this->db->where('p.id', (int)$id);
        return $this->db->get()->row();
    }

    public function generate_payslip($user_id, $period, $remarks = '', $meta = []){
        $user_id = (int)$user_id;
        $period = trim($period);
        $struct = $this->get_structure($user_id);
        if (!$struct){
            return false;
        }
        $basic = (float)$struct->basic;
        $hra = (float)$struct->hra;

        $conv = isset($struct->conveyance_allow) ? (float)$struct->conveyance_allow : 0.0;
        $med  = isset($struct->medical_allow) ? (float)$struct->medical_allow : 0.0;
        $edu  = isset($struct->education_allow) ? (float)$struct->education_allow : 0.0;
        $spec = isset($struct->special_allow) ? (float)$struct->special_allow : 0.0;
        $profTax = isset($struct->professional_tax) ? (float)$struct->professional_tax : 0.0;
        $tds = isset($struct->tds) ? (float)$struct->tds : 0.0;

        // If components are all zero but aggregate allowances/deductions exist, fallback
        $allowSum = $conv + $med + $edu + $spec;
        if ($allowSum <= 0 && isset($struct->allowances)){
            $allowSum = (float)$struct->allowances;
        }
        $dedSum = $profTax + $tds;
        if ($dedSum <= 0 && isset($struct->deductions)){
            $dedSum = (float)$struct->deductions;
        }

        $gross = $basic + $hra + $allowSum;
        $net = $gross - $dedSum;
        if ($net < 0) { $net = 0; }

        $data = [
            'basic' => $basic,
            'hra' => $hra,
            'conveyance_allow' => $conv,
            'medical_allow' => $med,
            'education_allow' => $edu,
            'special_allow' => $spec,
            'professional_tax' => $profTax,
            'tds' => $tds,
            'allowances' => $allowSum,
            'deductions' => $dedSum,
            'gross' => $gross,
            'net' => $net,
            'remarks' => $remarks,
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        // Optional metadata: pay_mode, bank, days/leaves
        if (is_array($meta)){
            $map = [
                'pay_mode' => 'pay_mode',
                'bank_name' => 'bank_name',
                'bank_ac_no' => 'bank_ac_no',
                'pan_no' => 'pan_no',
                'location' => 'location',
                'payment_days' => 'payment_days',
                'present_days' => 'present_days',
                'paid_leaves' => 'paid_leaves',
                'leave_without_pay' => 'leave_without_pay',
                'balance_leaves' => 'balance_leaves',
            ];
            foreach ($map as $k => $col){
                if (array_key_exists($k, $meta)){
                    $val = $meta[$k];
                    if (in_array($k, ['payment_days','present_days','paid_leaves','leave_without_pay','balance_leaves'], true)){
                        $val = ($val === '' ? null : (float)$val);
                    }
                    $data[$col] = $val;
                }
            }
        }

        // Upsert payslip for user+period
        $existing = $this->db->get_where($this->table_payslips, ['user_id' => $user_id, 'period' => $period])->row();
        if ($existing){
            $this->db->where('id', (int)$existing->id)->update($this->table_payslips, $data);
            return (int)$existing->id;
        }
        $data['user_id'] = $user_id;
        $data['period'] = $period;
        $this->db->insert($this->table_payslips, $data);
        return (int)$this->db->insert_id();
    }
}
