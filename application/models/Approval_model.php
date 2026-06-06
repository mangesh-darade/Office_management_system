<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Approval_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    public function ensure_schema() {
        static $done = false;
        if ($done) { return; }
        $done = true;
        // Table: approval_flows
        if (!$this->db->table_exists('approval_flows')) {
            $this->db->query("CREATE TABLE `approval_flows` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL,
                `is_active` tinyint(1) DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_module_active` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            // Allow multiple active flows per module? Probably simpler to have one per module for now.
            // Changed Index to unique per module to ensure one active flow.
        }

        // Table: approval_steps
        if (!$this->db->table_exists('approval_steps')) {
            $this->db->query("CREATE TABLE `approval_steps` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `flow_id` int(11) NOT NULL,
                `step_order` int(11) NOT NULL,
                `approver_type` enum('role','user','manager','department_head') NOT NULL DEFAULT 'manager',
                `approver_value` int(11) DEFAULT NULL COMMENT 'role_id or user_id',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_flow` (`flow_id`),
                KEY `idx_order` (`flow_id`, `step_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        // Table: approval_requests
        if (!$this->db->table_exists('approval_requests')) {
            $this->db->query("CREATE TABLE `approval_requests` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `flow_id` int(11) NOT NULL,
                `module` varchar(50) NOT NULL,
                `module_record_id` int(11) NOT NULL,
                `requester_id` int(11) NOT NULL,
                `current_step_order` int(11) NOT NULL DEFAULT 1,
                `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_requester` (`requester_id`),
                KEY `idx_module_record` (`module`, `module_record_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        // Table: approval_logs
        if (!$this->db->table_exists('approval_logs')) {
            $this->db->query("CREATE TABLE `approval_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `request_id` int(11) NOT NULL,
                `step_order` int(11) NOT NULL,
                `approver_id` int(11) NOT NULL,
                `action` enum('approved','rejected') NOT NULL,
                `comments` text,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_request` (`request_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
    }

    // --- Configuration Logic ---

    public function get_flows() {
        return $this->db->get('approval_flows')->result();
    }

    public function get_flow($id) {
        $flow = $this->db->get_where('approval_flows', ['id' => $id])->row();
        if ($flow) {
            $flow->steps = $this->db->order_by('step_order', 'ASC')
                                   ->get_where('approval_steps', ['flow_id' => $id])
                                   ->result();
        }
        return $flow;
    }

    public function get_active_flow_by_module($module) {
        $flow = $this->db->get_where('approval_flows', ['module' => $module, 'is_active' => 1])->row();
        if ($flow) {
            $flow->steps = $this->db->order_by('step_order', 'ASC')
                                   ->get_where('approval_steps', ['flow_id' => $flow->id])
                                   ->result();
        }
        return $flow;
    }

    public function save_flow($id, $data, $steps) {
        $this->db->trans_start();
        
        // Save Flow Header
        if ($id) {
            $this->db->where('id', $id)->update('approval_flows', $data);
        } else {
            // Check if active flow exists for this module
            if ($data['is_active']) {
                $this->db->where('module', $data['module'])->update('approval_flows', ['is_active' => 0]);
            }
            $this->db->insert('approval_flows', $data);
            $id = $this->db->insert_id();
        }

        // Save Steps
        $this->db->delete('approval_steps', ['flow_id' => $id]);
        foreach ($steps as $index => $step) {
            $step['flow_id'] = $id;
            $step['step_order'] = $index + 1;
            $this->db->insert('approval_steps', $step);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_flow($id) {
        $id = (int)$id;
        $this->db->trans_start();
        $this->db->where('flow_id', $id)->delete('approval_steps');
        $this->db->where('id', $id)->delete('approval_flows');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // --- Runtime Logic ---

    /**
     * Start an approval process
     */
    public function initiate_approval($module, $record_id, $requester_id) {
        $flow = $this->get_active_flow_by_module($module);
        
        // If no flow defined, auto-approve or return false depending on policy.
        // For Enterprise HRMS, usually implies direct approval if no flow.
        if (!$flow || empty($flow->steps)) {
            return ['status' => 'approved', 'auto_approved' => true];
        }

        $data = [
            'flow_id' => $flow->id,
            'module' => $module,
            'module_record_id' => $record_id,
            'requester_id' => $requester_id,
            'current_step_order' => 1,
            'status' => 'pending'
        ];
        $this->db->insert('approval_requests', $data);
        $request_id = $this->db->insert_id();
        
        return ['status' => 'pending', 'request_id' => $request_id, 'next_step' => $flow->steps[0]];
    }

    public function get_pending_request($module, $record_id) {
        return $this->db->where('module', $module)
                        ->where('module_record_id', $record_id)
                        ->where('status', 'pending')
                        ->get('approval_requests')
                        ->row();
    }
    
    public function approve_request($request_id, $approver_id, $comments = '') {
        $request = $this->db->get_where('approval_requests', ['id' => $request_id])->row();
        if (!$request || $request->status !== 'pending') return false;

        $flow = $this->get_flow($request->flow_id);
        if (!$flow) return false;

        // Log the approval
        $this->db->insert('approval_logs', [
            'request_id' => $request->id,
            'step_order' => $request->current_step_order,
            'approver_id' => $approver_id,
            'action' => 'approved',
            'comments' => $comments
        ]);

        // Check if there are more steps
        $total_steps = count($flow->steps);
        if ($request->current_step_order < $total_steps) {
            // Move to next step
            $this->db->where('id', $request->id)->update('approval_requests', [
                'current_step_order' => $request->current_step_order + 1
            ]);
            return 'pending_next_approval';
        } else {
            // All steps completed
            $this->db->where('id', $request->id)->update('approval_requests', [
                'status' => 'approved',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return 'approved';
        }
    }

    public function reject_request($request_id, $approver_id, $comments = '') {
        $this->db->insert('approval_logs', [
            'request_id' => $request_id,
            'step_order' => 0, // 0 for rejection usually or current step
            'approver_id' => $approver_id,
            'action' => 'rejected',
            'comments' => $comments
        ]);

        $this->db->where('id', $request_id)->update('approval_requests', [
            'status' => 'rejected',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return 'rejected';
    }

    // --- User Specific Logic ---

    public function get_pending_requests_for_user($user_id) {
        // This is complex because we need to match user against the current step's requirements
        // A cleaner approach for MVP: Fetch all pending requests, then filter in PHP.
        // For production scale, this should be optimized with complex SQL.
        
        $requests = $this->db->select('approval_requests.*, approval_steps.approver_type, approval_steps.approver_value, approval_flows.name as flow_name')
                             ->from('approval_requests')
                             ->join('approval_flows', 'approval_flows.id = approval_requests.flow_id')
                             ->join('approval_steps', 'approval_steps.flow_id = approval_requests.flow_id AND approval_steps.step_order = approval_requests.current_step_order')
                             ->where('approval_requests.status', 'pending')
                             ->get()
                             ->result();

        $pending = [];
        foreach ($requests as $req) {
            if ($this->can_user_approve($req->id, $user_id, $req)) {
                $pending[] = $req;
            }
        }
        return $pending;
    }

    public function can_user_approve($request_id, $user_id, $request_obj = null) {
        if (!$request_obj) {
            $request_obj = $this->db->select('approval_requests.*, approval_steps.approver_type, approval_steps.approver_value')
                                    ->from('approval_requests')
                                    ->join('approval_steps', 'approval_steps.flow_id = approval_requests.flow_id AND approval_steps.step_order = approval_requests.current_step_order')
                                    ->where('approval_requests.id', $request_id)
                                    ->get()
                                    ->row();
        }
        
        if (!$request_obj || $request_obj->status !== 'pending') {
            return false;
        }

        // 1. Admin Override (optional, usually admins can approve anything)
        // $user = $this->db->get_where('users', ['id' => $user_id])->row();
        // if ($user && $user->role_id == 1) return true;

        switch ($request_obj->approver_type) {
            case 'user':
                return (int)$request_obj->approver_value == (int)$user_id;
            
            case 'role':
                $user = $this->db->select('role_id')->get_where('users', ['id' => $user_id])->row();
                return $user && (int)$user->role_id == (int)$request_obj->approver_value;

            case 'manager':
                // Get requester's manager via reporting_to column (if it exists)
                if (!$this->db->table_exists('employees') || !schema_table_has_column($this->db, 'employees', 'reporting_to')) {
                    return false;
                }
                $employee = $this->db->get_where('employees', ['user_id' => $request_obj->requester_id])->row();
                return $employee && isset($employee->reporting_to) && (int)$employee->reporting_to == (int)$user_id;

            case 'department_head':
                // Get requester's department head
                $employee = $this->db->get_where('employees', ['user_id' => $request_obj->requester_id])->row();
                if (!$employee) return false;
                
                // Fix: employees table often stores department name in 'department' column
                $dept = null;
                if (isset($employee->department_id)) {
                    $dept = $this->db->get_where('departments', ['id' => $employee->department_id])->row();
                } else if (!empty($employee->department)) {
                     $dept = $this->db->get_where('departments', ['dept_name' => $employee->department])->row();
                }
                
                // Dept head is 'manager_id' in departments table
                return $dept && isset($dept->manager_id) && (int)$dept->manager_id == (int)$user_id;
        }

        return false;
    }
}

