<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_payments extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'coaching', 'permission']);
        $this->load->library('session');
        $this->load->model('Coaching_model', 'coaching');
    }

    public function pay($installment_id)
    {
        if (!(int) $this->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }

        $row = $this->coaching->installment_row((int) $installment_id);
        if (!$row || $row->status === 'paid') {
            show_404();
            return;
        }

        $settings = $this->coaching->payment_settings();
        $order_id = null;
        $error = null;

        if ($settings && $settings->gateway === 'razorpay' && $settings->is_active && $settings->key_id) {
            $rzp = coaching_razorpay();
            if ($rzp->is_configured()) {
                $paise = (int) round((float) $row->amount * 100);
                $receipt = 'INST-' . (int) $installment_id . '-' . time();
                $result = $rzp->create_order($paise, $receipt, [
                    'installment_id' => (string) $installment_id,
                ]);
                if ($result['success'] && !empty($result['order']['id'])) {
                    $order_id = $result['order']['id'];
                    $this->coaching->payment_order_save((int) $installment_id, $order_id, $paise);
                } else {
                    $error = isset($result['error']) ? $result['error'] : 'Could not create Razorpay order';
                }
            }
        }

        $this->load->helper('company');
        $this->load->view('coaching/billing/pay', [
            'installment' => $row,
            'settings' => $settings,
            'razorpay_order_id' => $order_id,
            'razorpay_error' => $error,
        ]);
    }

    /**
     * POST after Razorpay Checkout success (client-side handler).
     */
    public function verify()
    {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
        if (!(int) $this->session->userdata('user_id')) {
            $this->output->set_status_header(401)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Login required']));
            return;
        }

        $order_id = trim((string) $this->input->post('razorpay_order_id'));
        $payment_id = trim((string) $this->input->post('razorpay_payment_id'));
        $signature = trim((string) $this->input->post('razorpay_signature'));

        $rzp = coaching_razorpay();
        if (!$rzp->verify_checkout_signature($order_id, $payment_id, $signature)) {
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Payment verification failed']));
            return;
        }

        $local = $this->coaching->payment_order_by_rzp_id($order_id);
        if (!$local) {
            $this->output->set_status_header(404)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Order not found']));
            return;
        }

        if ($local->status !== 'paid') {
            $this->coaching->mark_installment_paid((int) $local->installment_id, 'razorpay', $payment_id);
            $this->coaching->payment_order_mark_paid($order_id, $payment_id);
        }

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'redirect' => site_url('coaching-portal'),
        ]));
    }

    /** Legacy route — forwards to webhooks controller */
    public function webhook()
    {
        redirect('coaching-webhooks/razorpay');
    }

    public function confirm_manual($installment_id)
    {
        $role_id = (int) $this->session->userdata('role_id');
        if ($role_id !== 1 && $role_id !== ROLE_COACHING_CLIENT && !has_module_access('coaching_billing')) {
            show_error('Forbidden', 403);
        }
        $row = $this->coaching->installment_row((int) $installment_id);
        if (!$row) {
            show_404();
            return;
        }
        $this->coaching->mark_installment_paid((int) $installment_id, 'manual', 'MANUAL-' . time());
        $this->session->set_flashdata('success', 'Payment recorded.');
        redirect($role_id === ROLE_COACHING_CLIENT ? 'coaching-portal' : 'coaching-billing/invoice/' . (int) $row->invoice_id);
    }

    public function success()
    {
        $this->load->view('coaching/billing/pay_success');
    }
}
