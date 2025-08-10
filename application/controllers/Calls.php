<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calls extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->library(['session']);
        $this->load->model('Call_model');
        if (!$this->session->userdata('user_id')) { redirect('login'); exit; }
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

    private function _json($arr) { $this->output->set_content_type('application/json')->set_output(json_encode($arr)); }
}
