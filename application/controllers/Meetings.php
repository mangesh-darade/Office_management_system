<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meetings extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'permission', 'api_integration'));
        $this->load->library(array('session'));
        $this->load->model('Meeting_model', 'meeting');
        $this->load->model('Chat_model', 'chat');

        require_module_access(array('calls', 'chats'), true);

        if (!$this->session->userdata('meeting_schema_ok')) {
            $this->meeting->ensure_schema();
            $this->session->set_userdata('meeting_schema_ok', true);
        }
    }

    // POST /meetings/start/{conversation_id}
    public function start($conversation_id) {
        $this->_join_payload($conversation_id, true);
    }

    // GET /meetings/join/{conversation_id}
    public function join($conversation_id) {
        $this->_join_payload($conversation_id, false);
    }

    // GET /meetings/status/{conversation_id}
    public function status($conversation_id) {
        $conversation_id = (int) $conversation_id;
        $user_id = (int) $this->session->userdata('user_id');
        if (!$this->meeting->is_participant($conversation_id, $user_id)) {
            return $this->_json(array('ok' => false, 'error' => 'Not a participant'));
        }
        $active = $this->meeting->get_active($conversation_id);
        return $this->_json(array(
            'ok' => true,
            'has_active' => (bool) $active,
            'host_user_id' => $active ? (int) $active->host_user_id : null,
        ));
    }

    // POST /meetings/end/{conversation_id}
    public function end($conversation_id) {
        $conversation_id = (int) $conversation_id;
        $user_id = (int) $this->session->userdata('user_id');
        if (!$this->meeting->is_participant($conversation_id, $user_id)) {
            return $this->_json(array('ok' => false, 'error' => 'Not a participant'));
        }
        $active = $this->meeting->get_active($conversation_id);
        if ($active && (int) $active->host_user_id === $user_id) {
            $this->meeting->mark_ended($conversation_id);
        }
        return $this->_json(array('ok' => true));
    }

    // GET /meetings/join-scheduled/{id}
    public function join_scheduled($id) {
        $user_id = (int) $this->session->userdata('user_id');
        $user_email = $this->session->userdata('email') ? $this->session->userdata('email') : '';
        $row = $this->meeting->get_scheduled((int) $id);
        if (!$row || $row->status === 'cancelled') {
            return $this->_json(array('ok' => false, 'error' => 'Meeting not found'));
        }
        if (!$this->meeting->can_access_scheduled($row, $user_id, $user_email)) {
            return $this->_json(array('ok' => false, 'error' => 'Access denied'));
        }
        $this->_join_payload_for_room(
            $row->room_name,
            $row->title,
            $user_id,
            ((int) $row->host_user_id === $user_id),
            $row->conversation_id ? (int) $row->conversation_id : 0
        );
    }

    // GET /meetings/participants/{conversation_id}
    public function participants($conversation_id) {
        $conversation_id = (int) $conversation_id;
        $user_id = (int) $this->session->userdata('user_id');
        if (!$this->meeting->is_participant($conversation_id, $user_id)) {
            return $this->_json(array('ok' => false, 'error' => 'Not a participant'));
        }
        $rows = $this->meeting->list_participants($conversation_id);
        $participants = array();
        foreach ($rows as $row) {
            $participants[] = array(
                'id' => (int) $row->id,
                'email' => $row->email,
                'name' => $row->name ? $row->name : $row->email,
                'role' => $row->role,
            );
        }
        return $this->_json(array('ok' => true, 'participants' => $participants));
    }

    // POST /meetings/schedule
    public function schedule() {
        $user_id = (int) $this->session->userdata('user_id');
        $conversation_id = (int) $this->input->post('conversation_id');
        $title = trim((string) $this->input->post('title'));
        $scheduled_at = trim((string) $this->input->post('scheduled_at'));
        $duration_minutes = (int) $this->input->post('duration_minutes');
        $notes = trim((string) $this->input->post('notes'));
        $participant_emails = trim((string) $this->input->post('participant_emails'));

        if ($title === '' || $scheduled_at === '') {
            return $this->_json(array('ok' => false, 'error' => 'Title and scheduled time are required'));
        }
        if ($conversation_id && !$this->meeting->is_participant($conversation_id, $user_id)) {
            return $this->_json(array('ok' => false, 'error' => 'Not a participant'));
        }
        if ($duration_minutes <= 0) {
            $duration_minutes = 60;
        }

        $room_name = $conversation_id
            ? $this->meeting->get_or_create_room_name($conversation_id)
            : $this->meeting->generate_secure_room_name();

        $id = $this->meeting->create_scheduled(array(
            'conversation_id' => $conversation_id ? $conversation_id : null,
            'title' => $title,
            'room_name' => $room_name,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($scheduled_at)),
            'duration_minutes' => $duration_minutes,
            'created_by' => $user_id,
            'host_user_id' => $user_id,
            'status' => 'scheduled',
            'participants_json' => $participant_emails !== '' ? json_encode(array_map('trim', explode(',', $participant_emails))) : null,
            'notes' => $notes !== '' ? $notes : null,
        ));

        $join_url = $conversation_id
            ? site_url('chats/app?open=' . $conversation_id . '&meeting=' . $id)
            : site_url('chats/app?meeting=' . $id);
        $this->_send_meeting_invite_email($id, $title, $scheduled_at, $duration_minutes, $join_url, $participant_emails);

        return $this->_json(array(
            'ok' => true,
            'meeting_id' => $id,
            'room_name' => $room_name,
            'join_url' => $join_url,
        ));
    }

    // GET /meetings/list
    public function list_meetings() {
        $user_id = (int) $this->session->userdata('user_id');
        $rows = $this->meeting->list_upcoming($user_id);
        $meetings = array();
        foreach ($rows as $row) {
            $join_url = $row->conversation_id
                ? site_url('chats/app?open=' . (int) $row->conversation_id . '&meeting=' . (int) $row->id)
                : site_url('chats/app?meeting=' . (int) $row->id);
            $meetings[] = array(
                'id' => (int) $row->id,
                'conversation_id' => $row->conversation_id ? (int) $row->conversation_id : null,
                'title' => $row->title,
                'scheduled_at' => $row->scheduled_at,
                'duration_minutes' => (int) $row->duration_minutes,
                'status' => $row->status,
                'join_url' => $join_url,
            );
        }
        return $this->_json(array('ok' => true, 'meetings' => $meetings));
    }

    // POST /meetings/cancel/{id}
    public function cancel($id) {
        $user_id = (int) $this->session->userdata('user_id');
        $ok = $this->meeting->cancel_scheduled((int) $id, $user_id);
        if (!$ok) {
            return $this->_json(array('ok' => false, 'error' => 'Unable to cancel meeting'));
        }
        return $this->_json(array('ok' => true));
    }

    // GET /meetings/scheduled/{id}
    public function scheduled($id) {
        $user_id = (int) $this->session->userdata('user_id');
        $user_email = $this->session->userdata('email') ? $this->session->userdata('email') : '';
        $row = $this->meeting->get_scheduled((int) $id);
        if (!$row) {
            return $this->_json(array('ok' => false, 'error' => 'Meeting not found'));
        }
        if (!$this->meeting->can_access_scheduled($row, $user_id, $user_email)) {
            return $this->_json(array('ok' => false, 'error' => 'Access denied'));
        }
        return $this->_json(array(
            'ok' => true,
            'meeting' => array(
                'id' => (int) $row->id,
                'conversation_id' => $row->conversation_id ? (int) $row->conversation_id : null,
                'title' => $row->title,
                'scheduled_at' => $row->scheduled_at,
                'duration_minutes' => (int) $row->duration_minutes,
                'status' => $row->status,
                'notes' => $row->notes,
            ),
        ));
    }

    private function _join_payload($conversation_id, $is_start) {
        $conversation_id = (int) $conversation_id;
        $user_id = (int) $this->session->userdata('user_id');

        if (!$this->meeting->is_participant($conversation_id, $user_id)) {
            return $this->_json(array('ok' => false, 'error' => 'Not a participant of this conversation'));
        }

        $jitsi = get_jitsi_config();
        if (!$jitsi['enabled']) {
            return $this->_json(array('ok' => false, 'error' => 'Jitsi is not configured', 'use_fallback' => true));
        }

        $room_name = $this->meeting->get_or_create_room_name($conversation_id);
        $active = $this->meeting->get_active($conversation_id);
        $is_moderator = $this->meeting->is_conversation_creator($conversation_id, $user_id)
            || ($is_start && !$active)
            || ($active && (int) $active->host_user_id === $user_id);

        if ($is_start) {
            $this->meeting->mark_active($conversation_id, $room_name, $user_id);
        }

        $conv = $this->db->get_where('conversations', array('id' => $conversation_id))->row();
        $title = '';
        if ($conv) {
            $title = $conv->title ? $conv->title : 'Conversation #' . $conversation_id;
        }

        return $this->_build_join_response($room_name, $title, $user_id, $is_moderator, $jitsi, (bool) $active);
    }

    private function _join_payload_for_room($room_name, $title, $user_id, $is_moderator, $conversation_id) {
        $jitsi = get_jitsi_config();
        if (!$jitsi['enabled']) {
            return $this->_json(array('ok' => false, 'error' => 'Jitsi is not configured'));
        }
        if ($conversation_id && $this->meeting->is_participant($conversation_id, $user_id)) {
            $this->meeting->mark_active($conversation_id, $room_name, $user_id);
        }
        return $this->_build_join_response($room_name, $title, $user_id, $is_moderator, $jitsi, false);
    }

    private function _build_join_response($room_name, $title, $user_id, $is_moderator, $jitsi, $had_active) {
        $display_name = $this->session->userdata('name');
        if (!$display_name) {
            $display_name = $this->session->userdata('email');
        }
        if (!$display_name) {
            $display_name = 'User ' . $user_id;
        }
        $email = $this->session->userdata('email') ? $this->session->userdata('email') : '';

        $jwt = $this->meeting->generate_jitsi_jwt(
            $room_name,
            $display_name,
            $email,
            $is_moderator,
            $jitsi['domain'],
            $jitsi['app_id'],
            $jitsi['jwt_secret']
        );

        return $this->_json(array(
            'ok' => true,
            'room_name' => $room_name,
            'domain' => $jitsi['domain'],
            'jwt' => $jwt,
            'is_moderator' => $is_moderator,
            'display_name' => $display_name,
            'conversation_title' => $title,
            'use_jitsi' => true,
            'has_active_meeting' => $had_active,
            'security_warning' => $jitsi['security_warning'],
        ));
    }

    private function _send_meeting_invite_email($meeting_id, $title, $scheduled_at, $duration_minutes, $join_url, $participant_emails) {
        if ($participant_emails === '') {
            return;
        }

        $emails = array_filter(array_map('trim', explode(',', $participant_emails)));
        if (empty($emails)) {
            return;
        }

        $this->load->model('Integration_model', 'integration');
        $start_ts = strtotime($scheduled_at);
        $end_ts = $start_ts + ($duration_minutes * 60);
        $ical = $this->integration->generate_ical_feed(array(
            array(
                'id' => 'meeting-' . $meeting_id,
                'start' => date('Y-m-d H:i:s', $start_ts),
                'end' => date('Y-m-d H:i:s', $end_ts),
                'title' => $title,
                'description' => 'Join: ' . $join_url,
            ),
        ));

        $body = '<p>You are invited to a scheduled meeting: <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong></p>';
        $body .= '<p>When: ' . htmlspecialchars(date('Y-m-d H:i', $start_ts), ENT_QUOTES, 'UTF-8') . '</p>';
        $body .= '<p><a href="' . htmlspecialchars($join_url, ENT_QUOTES, 'UTF-8') . '">Join meeting</a></p>';

        $this->load->helper('api_integration');
        $smtp = get_smtp_credentials();
        if (!empty($smtp['host'])) {
            $this->load->library('email');
            $this->email->initialize(array(
                'protocol' => 'smtp',
                'smtp_host' => $smtp['host'],
                'smtp_port' => $smtp['port'] ? $smtp['port'] : 587,
                'smtp_user' => $smtp['user'],
                'smtp_pass' => $smtp['password'],
                'smtp_crypto' => $smtp['encryption'] ? $smtp['encryption'] : 'tls',
                'mailtype' => 'html',
                'charset' => 'utf-8',
            ));
            foreach ($emails as $to) {
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $tmp_ical = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oms-meeting-' . $meeting_id . '-' . md5($to) . '.ics';
                file_put_contents($tmp_ical, $ical);
                $this->email->clear(true);
                $this->email->from($smtp['from_email'] ? $smtp['from_email'] : 'noreply@localhost', $smtp['from_name'] ? $smtp['from_name'] : 'Office Portal');
                $this->email->to($to);
                $this->email->subject('Meeting invite: ' . $title);
                $this->email->message($body);
                $this->email->attach($tmp_ical, 'attachment', 'meeting.ics', 'text/calendar');
                $this->email->send();
                if (is_file($tmp_ical)) {
                    @unlink($tmp_ical);
                }
            }
        }
    }

    private function _json($arr) {
        $this->output->set_content_type('application/json')->set_output(json_encode($arr));
    }
}
