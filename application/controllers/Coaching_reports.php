<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_reports extends Coaching_Controller {

    protected $coaching_permission = 'coaching_reports';

    public function index()
    {
        $stats = $this->coaching->dashboard_stats();
        $by_coach = $this->db->query("
            SELECT u.name AS coach_name, COUNT(DISTINCT cc.id) AS client_count
            FROM coaching_coaches c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN coaching_clients cc ON cc.primary_coach_id = c.id AND cc.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.id, u.name
            ORDER BY client_count DESC
        ")->result();
        $retention = $this->db->query("
            SELECT status, COUNT(*) AS cnt FROM coaching_clients GROUP BY status
        ")->result();
        $this->load->view('coaching/reports/index', compact('stats', 'by_coach', 'retention'));
    }
}
