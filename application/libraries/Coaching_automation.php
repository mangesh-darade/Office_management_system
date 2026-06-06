<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Coaching cron jobs: automation rules and session email reminders.
 * Extracted from Coaching_model for clearer separation of scheduled tasks.
 */
class Coaching_automation {

    /** @var CI_Controller */
    protected $CI;

    /** @var Coaching_model */
    protected $coaching;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('Coaching_model', 'coaching');
        $this->coaching = $this->CI->coaching;
    }

    /**
     * Run active coaching_automation_rules (e.g. stale goal notifications).
     *
     * @return int Number of actions taken
     */
    public function run_automation_cron()
    {
        $this->CI->load->helper('coaching_notify');
        $rules = $this->CI->db->where('is_active', 1)->get('coaching_automation_rules')->result();
        $actions = 0;

        foreach ($rules as $rule) {
            if ($rule->trigger_type !== 'goal_stale_days') {
                continue;
            }

            $cfg = json_decode($rule->trigger_config ? $rule->trigger_config : '{}', true);
            $days = (is_array($cfg) && isset($cfg['days'])) ? (int) $cfg['days'] : 7;
            $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
            $stale = $this->CI->db->where('status', 'active')
                ->where('updated_at <', $cutoff)
                ->get('coaching_goals')->result();

            foreach ($stale as $g) {
                $client = $this->coaching->client_get($g->coaching_client_id);
                $coach_name = '';
                $coach_email = null;

                if ($client && !empty($client->primary_coach_id)) {
                    $coach = $this->CI->db->select('u.email, u.name')
                        ->from('coaching_coaches c')
                        ->join('users u', 'u.id = c.user_id', 'left')
                        ->where('c.id', (int) $client->primary_coach_id)
                        ->get()->row();
                    if ($coach) {
                        $coach_name = $coach->name;
                        $coach_email = $coach->email;
                    }
                }

                $subject = 'Stale coaching goal: ' . $g->title;
                $body = '<p>Goal <strong>' . htmlspecialchars($g->title) . '</strong> for '
                    . htmlspecialchars($client ? $client->full_name : 'client')
                    . ' has had no update in ' . (int) $days . ' days.</p>';
                $action = $rule->action_type ? $rule->action_type : 'log_reminder';

                if ($action === 'email_coach' && $coach_email) {
                    if (coaching_send_mail($coach_email, $subject, '<p>Hi ' . htmlspecialchars($coach_name) . ',</p>' . $body)) {
                        $actions++;
                    }
                } elseif ($action === 'email_client' && $client && $client->email) {
                    if (coaching_send_mail($client->email, $subject, '<p>Hi ' . htmlspecialchars($client->full_name) . ',</p>' . $body)) {
                        $actions++;
                    }
                } else {
                    log_message('info', 'Coaching automation: stale goal #' . (int) $g->id);
                    $actions++;
                }
            }
        }

        return $actions;
    }

    /**
     * Send 24h and 1h session reminder emails.
     *
     * @return int Number of reminders sent
     */
    public function process_session_reminder_cron()
    {
        $this->CI->load->helper('coaching_notify');
        $sent = 0;

        foreach ($this->sessions_needing_reminder(24, 'reminder_24h_sent') as $s) {
            if (coaching_email_session_reminder((int) $s->id, '24h')) {
                $this->session_mark_reminder_sent((int) $s->id, 'reminder_24h_sent');
                $sent++;
            }
        }

        foreach ($this->sessions_needing_reminder(1, 'reminder_1h_sent') as $s) {
            if (coaching_email_session_reminder((int) $s->id, '1h')) {
                $this->session_mark_reminder_sent((int) $s->id, 'reminder_1h_sent');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param int    $hours_before
     * @param string $flag_column
     * @return array
     */
    public function sessions_needing_reminder($hours_before, $flag_column)
    {
        if (!schema_table_has_column($this->CI->db, 'coaching_sessions', $flag_column)) {
            return array();
        }

        $from = date('Y-m-d H:i:s', strtotime('+' . ($hours_before - 1) . ' hours'));
        $to = date('Y-m-d H:i:s', strtotime('+' . ($hours_before + 1) . ' hours'));

        return $this->CI->db
            ->where('status', 'scheduled')
            ->where($flag_column, 0)
            ->where('scheduled_at >=', $from)
            ->where('scheduled_at <=', $to)
            ->get('coaching_sessions')
            ->result();
    }

    /**
     * @param int    $session_id
     * @param string $flag_column
     * @return void
     */
    public function session_mark_reminder_sent($session_id, $flag_column)
    {
        if (schema_table_has_column($this->CI->db, 'coaching_sessions', $flag_column)) {
            $this->CI->db->where('id', (int) $session_id)->update('coaching_sessions', array($flag_column => 1));
        }
    }
}
