<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_model extends CI_Model {
    private $table = 'attendance';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema(){
        static $done = false;
        if ($done) { return; }
        $done = true;
        if ($this->db->table_exists($this->table)){
            $fields = $this->db->list_fields($this->table);
            if (!in_array('location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `location_name` VARCHAR(255) NULL");
            }
            // Add check-in location fields
            if (!in_array('checkin_lat', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_lat` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkin_lng', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_lng` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkin_location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkin_location_name` VARCHAR(255) NULL");
            }
            // Add check-out location fields
            if (!in_array('checkout_lat', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_lat` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkout_lng', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_lng` DECIMAL(10,7) NULL");
            }
            if (!in_array('checkout_location_name', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `checkout_location_name` VARCHAR(255) NULL");
            }
            if (!in_array('shift_id', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `shift_id` INT(11) NULL DEFAULT NULL");
            }
            if (!in_array('status', $fields, true)) {
                $this->db->query("ALTER TABLE `".$this->table."` ADD `status` ENUM('present', 'absent', 'late', 'early_leave', 'half_day') DEFAULT 'present'");
            }

            // Add composite index for the most frequent query pattern (user_id + date)
            $date_col = in_array('att_date', $fields, true) ? 'att_date' : 'date';
            $idx_check = $this->db->query(
                "SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'idx_user_date'"
            )->result();
            if (empty($idx_check)) {
                $this->db->query(
                    "ALTER TABLE `{$this->table}` ADD INDEX `idx_user_date` (`user_id`, `{$date_col}`)"
                );
            }
        }
    }

    /**
     * Returns the canonical column names for the attendance table.
     * Cached statically so field_exists() is called only once per request.
     */
    public function get_columns()
    {
        static $cols = null;
        if ($cols !== null) { return $cols; }
        $cols = [
            'date'      => $this->db->field_exists('att_date',   $this->table) ? 'att_date'   : 'date',
            'punch_in'  => $this->db->field_exists('punch_in',   $this->table) ? 'punch_in'   : 'check_in',
            'punch_out' => $this->db->field_exists('punch_out',  $this->table) ? 'punch_out'  : 'check_out',
        ];
        return $cols;
    }

    /**
     * Find a single attendance record by ID.
     */
    public function find($id)
    {
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }

    /**
     * Find today's attendance record for a user.
     */
    public function find_today($user_id)
    {
        $cols = $this->get_columns();
        return $this->db->where('user_id', (int)$user_id)
                        ->where($cols['date'], date('Y-m-d'))
                        ->get($this->table)
                        ->row();
    }

    /**
     * Insert a new attendance record.
     */
    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing attendance record.
     */
    public function update($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete an attendance record.
     */
    public function delete($id)
    {
        $this->db->where('id', (int)$id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Calculate attendance status based on punch-in time vs shift start.
     *
     * @param string $punch_in     HH:MM:SS
     * @param string $shift_start  HH:MM:SS
     * @param int    $grace_minutes
     * @return string  'present' | 'late'
     */
    public function calculate_status($punch_in, $shift_start = '09:30:00', $grace_minutes = 15)
    {
        if (empty($punch_in) || empty($shift_start)) { return 'present'; }
        $pi   = strtotime($punch_in);
        $ss   = strtotime($shift_start);
        $late = $ss + ($grace_minutes * 60);
        return ($pi > $late) ? 'late' : 'present';
    }

    /**
     * Get monthly attendance summary counts for a user.
     *
     * @param int    $user_id
     * @param string $month   'Y-m' format
     * @return object  {total, present, late, absent, early_leave}
     */
    public function get_monthly_summary($user_id, $month)
    {
        $cols = $this->get_columns();
        $this->db->select([
            'COUNT(*) AS total',
            "SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present",
            "SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late",
            "SUM(CASE WHEN status = 'early_leave' THEN 1 ELSE 0 END) AS early_leave",
        ]);
        $this->db->from($this->table);
        $this->db->where('user_id', (int)$user_id);
        $this->db->where("DATE_FORMAT({$cols['date']}, '%Y-%m')", $month);
        return $this->db->get()->row();
    }

    /**
     * Get attendance statistics across all users (for dashboard / reports).
     *
     * @param int $days  Number of past days to include
     * @return array
     */
    public function get_statistics($days = 30)
    {
        $cols = $this->get_columns();
        $days = (int)$days;
        $sql  = "SELECT DATE(`{$cols['date']}`) AS d, COUNT(*) AS cnt
                 FROM `{$this->table}`
                 WHERE `{$cols['date']}` >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
                 GROUP BY DATE(`{$cols['date']}`)
                 ORDER BY d ASC";
        return $this->db->query($sql)->result();
    }
}
