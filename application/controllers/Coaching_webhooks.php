<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public webhooks (no login): Razorpay, Meta WhatsApp inbound.
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

        // Never process payment events without a verified signature.
        if (!$rzp->is_configured()) {
            log_message('error', 'Coaching_webhooks: Razorpay webhook hit while integration not configured.');
            $this->output->set_status_header(503);
            echo json_encode(['error' => 'Gateway not configured']);
            return;
        }
        if (!$rzp->verify_webhook_signature($raw, (string) $sig)) {
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
     * POST/GET Meta WhatsApp webhook (legacy coaching URL; same handler as whatsapp/webhook).
     * Route: coaching-webhooks/whatsapp-inbound
     */
    public function whatsapp_inbound()
    {
        handle_meta_whatsapp_webhook_http();
    }
}
