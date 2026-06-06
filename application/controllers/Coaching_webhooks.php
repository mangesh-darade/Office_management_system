<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public webhooks (no login): Razorpay, Twilio WhatsApp inbound.
 */
class Coaching_webhooks extends Coaching_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('api_integration');
    }

    /**
     * Webhook endpoints are public; skip coaching RBAC from Coaching_Controller.
     *
     * @return bool
     */
    protected function coaching_skip_access()
    {
        return true;
    }

    /**
     * POST Razorpay webhook — configure URL in Razorpay Dashboard.
     * Route: coaching-webhooks/razorpay
     */
    public function razorpay()
    {
        $raw = file_get_contents('php://input');
        $sig = $this->input->get_request_header('X-Razorpay-Signature', true);
        $rzp = coaching_razorpay();

        if ($rzp->is_configured() && !$rzp->verify_webhook_signature($raw, (string) $sig)) {
            $this->output->set_status_header(400);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }

        $event = json_decode($raw, true);
        if (!is_array($event) || empty($event['event'])) {
            $this->output->set_status_header(400);
            return;
        }

        if ($event['event'] === 'payment.captured' && !empty($event['payload']['payment']['entity'])) {
            $pay = $event['payload']['payment']['entity'];
            $order_id = isset($pay['order_id']) ? $pay['order_id'] : '';
            $payment_id = isset($pay['id']) ? $pay['id'] : '';
            if ($order_id !== '') {
                $local = $this->coaching->payment_order_by_rzp_id($order_id);
                if ($local && $local->status !== 'paid') {
                    $this->coaching->mark_installment_paid((int) $local->installment_id, 'razorpay', $payment_id);
                    $this->coaching->payment_order_mark_paid($order_id, $payment_id);
                }
            }
        }

        $this->output->set_content_type('application/json')->set_output(json_encode(['ok' => true]));
    }

    /**
     * POST Twilio WhatsApp inbound — set webhook on Twilio number to this URL.
     * Route: coaching-webhooks/whatsapp-inbound
     */
    public function whatsapp_inbound()
    {
        $creds = get_whatsapp_credentials();
        $auth_token = isset($creds['auth_token']) ? (string) $creds['auth_token'] : '';

        if ($auth_token !== '') {
            $signature = (string) $this->input->get_request_header('X-Twilio-Signature', true);
            $post_vars = $this->input->post(null, false);
            if (!is_array($post_vars)) {
                $post_vars = array();
            }

            if (!validate_twilio_webhook_signature(
                $signature,
                twilio_webhook_request_url(),
                $post_vars,
                $auth_token
            )) {
                log_message('error', 'Coaching_webhooks: invalid Twilio signature on whatsapp_inbound');
                $this->output->set_status_header(403);
                return;
            }
        } else {
            log_message('debug', 'Coaching_webhooks: Twilio auth token not configured; skipping signature check');
        }

        $from = (string) $this->input->post('From');
        $body = trim((string) $this->input->post('Body'));
        $profile = trim((string) $this->input->post('ProfileName'));

        if ($from === '' && $body === '') {
            $this->output->set_status_header(400);
            return;
        }

        $phone = preg_replace('/^whatsapp:/i', '', $from);
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        $lead_id = null;
        $existing = $this->db->where('phone', $phone)->order_by('id', 'DESC')->get('coaching_leads')->row();
        if (!$existing && $body !== '') {
            $lead_id = $this->coaching->lead_save([
                'full_name' => $profile !== '' ? $profile : ('WhatsApp ' . $phone),
                'phone' => $phone,
                'source' => 'whatsapp_inbound',
                'status' => 'new',
                'notes' => $body,
            ]);
        }

        $this->coaching->enquiry_save([
            'phone' => $phone,
            'contact_name' => $profile !== '' ? $profile : null,
            'message' => $body !== '' ? $body : '(empty message)',
            'status' => 'open',
            'lead_id' => $lead_id ?: ($existing ? (int) $existing->id : null),
        ]);

        $this->output->set_content_type('text/xml')->set_output(
            '<?xml version="1.0" encoding="UTF-8"?><Response></Response>'
        );
    }
}
