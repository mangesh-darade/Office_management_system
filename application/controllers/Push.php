<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Web Push subscription endpoints — session auth only (all logged-in users).
 */
class Push extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session']);
        $this->load->helper(['url', 'notifications_schema']);
        if (!$this->session->userdata('user_id')) {
            show_error('Unauthorized', 401);
        }
        notifications_schema_ensure_push_subscriptions($this->db);
    }

    /**
     * GET /push/vapid-public
     */
    public function vapid_public()
    {
        $this->load->library('Web_push');
        $keys = Web_push::ensure_vapid_keys();
        if (!$keys) {
            $this->_json(['ok' => false, 'error' => 'vapid_unavailable']);
            return;
        }
        $this->_json(['ok' => true, 'publicKey' => $keys['public']]);
    }

    /**
     * POST /push/subscribe
     */
    public function subscribe()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $endpoint = trim((string) $this->input->post('endpoint'));
        $p256dh = trim((string) $this->input->post('p256dh'));
        $auth = trim((string) $this->input->post('auth'));
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            $this->_json(['ok' => false, 'error' => 'invalid']);
            return;
        }

        $data = [
            'user_id' => $user_id,
            'endpoint' => $endpoint,
            'p256dh_key' => $p256dh,
            'auth_token' => $auth,
            'user_agent' => $this->input->user_agent(),
        ];
        $this->db->replace('push_subscriptions', $data);
        $this->_json(['ok' => true]);
    }

    /**
     * POST /push/unsubscribe
     */
    public function unsubscribe()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $endpoint = trim((string) $this->input->post('endpoint'));
        if ($endpoint === '') {
            $this->_json(['ok' => false, 'error' => 'invalid']);
            return;
        }
        $this->db->where('user_id', $user_id);
        $this->db->where('endpoint', $endpoint);
        $this->db->delete('push_subscriptions');
        $this->_json(['ok' => true]);
    }

    private function _json($arr)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($arr));
    }
}
