<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calls extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->library(['session']);
        $this->load->model('Call_model');
        // Calls controller serves AJAX/JSON only. If not logged in, return JSON 401 instead of HTML redirect.
        if (!$this->session->userdata('user_id')) {
            $this->output->set_status_header(401);
            $this->_json(['ok'=>false, 'error'=>'unauthorized']);
            exit;
        }
        $this->Call_model->ensure_schema();
    }

    // POST /calls/start/{conversation_id}
    public function start($conversation_id) {
        $conversation_id = (int)$conversation_id;
        $initiator = (int)$this->session->userdata('user_id');
        $call_id = $this->Call_model->start_call($conversation_id, $initiator);
        $this->_json(['ok'=>true,'call_id'=>$call_id]);
    }

    // POST /calls/signal/{call_id}
    public function signal($call_id) {
        $call_id = (int)$call_id;
        $from = (int)$this->session->userdata('user_id');
        $type = $this->input->post('type'); // offer|answer|ice
        $payload = $this->input->post('payload'); // JSON string
        $to_user_id = (int)$this->input->post('to_user_id'); // optional
        if (!$type || !$payload) { $this->_json(['ok'=>false,'error'=>'invalid']); return; }
        $id = $this->Call_model->add_signal($call_id, $from, $to_user_id ?: null, $type, $payload);
        $this->_json(['ok'=>true,'id'=>$id]);
    }

    // GET /calls/poll/{call_id}?since_id=123
    public function poll_signals($call_id) {
        $call_id = (int)$call_id;
        $since_id = (int)$this->input->get('since_id');
        $for_user = (int)$this->session->userdata('user_id');
        $rows = $this->Call_model->fetch_signals($call_id, $since_id, $for_user);
        $this->_json(['ok'=>true,'signals'=>$rows]);
    }

    // POST /calls/end/{call_id}
    public function end($call_id) {
        $call_id = (int)$call_id;
        $this->Call_model->end_call($call_id);
        $this->_json(['ok'=>true]);
    }

    // GET /calls/incoming/{conversation_id}?since_id=0
    // Returns recent 'offer' signals for the given conversation, excluding the current user's own offers
    public function poll_incoming($conversation_id) {
        $conversation_id = (int)$conversation_id;
        $since_id = (int)$this->input->get('since_id');
        $me = (int)$this->session->userdata('user_id');
        // Find latest offer(s) for this conversation in the last 2 minutes
        $this->db->select('s.id, s.call_id, s.from_user_id, s.to_user_id, s.type, s.payload, s.created_at, u.email AS from_email')
                 ->from('signaling_messages s')
                 ->join('calls c', 'c.id = s.call_id')
                 ->join('users u', 'u.id = s.from_user_id', 'left')
                 ->where('c.conversation_id', $conversation_id)
                 ->where('s.type', 'offer')
                 ->where('s.id >', $since_id)
                 ->where('s.from_user_id !=', $me)
                 ->where('s.created_at >=', date('Y-m-d H:i:s', time() - 120))
                 ->order_by('s.id', 'ASC');
        $rows = $this->db->get()->result();
        $this->_json(['ok'=>true, 'signals'=>$rows]);
    }

    // GET /calls/incoming-any?since_id=0
    // Returns recent 'offer' signals for any call where the current user is a participant (global polling)
    public function poll_incoming_any() {
        $since_id = (int)$this->input->get('since_id');
        $me = (int)$this->session->userdata('user_id');
        if (!$me) { return $this->_json(['ok'=>false, 'error'=>'unauthorized']); }
        // Join participants to ensure this user is part of the call's conversation
        $this->db->select('s.id, s.call_id, c.conversation_id, s.from_user_id, s.to_user_id, s.type, s.payload, s.created_at, u.email AS from_email')
                 ->from('signaling_messages s')
                 ->join('calls c', 'c.id = s.call_id')
                 ->join('call_participants cp', 'cp.call_id = c.id')
                 ->join('users u', 'u.id = s.from_user_id', 'left')
                 ->where('cp.user_id', $me)
                 ->where('s.type', 'offer')
                 ->where('s.id >', $since_id)
                 ->where('s.from_user_id !=', $me)
                 ->where('s.created_at >=', date('Y-m-d H:i:s', time() - 120))
                 ->order_by('s.id', 'ASC');
        $rows = $this->db->get()->result();
        $this->_json(['ok'=>true, 'signals'=>$rows]);
    }

    private function _json($arr) { $this->output->set_content_type('application/json')->set_output(json_encode($arr)); }
}
