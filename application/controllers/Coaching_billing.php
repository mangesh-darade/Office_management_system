<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_billing extends Coaching_Controller {

    protected $coaching_permission = 'coaching_billing';

    public function index()
    {
        $this->load->view('coaching/billing/index', [
            'programs' => $this->coaching->programs_all(),
            'invoices' => $this->coaching->invoices_all(),
            'clients' => $this->coaching->clients_all(),
        ]);
    }

    public function save_program()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $this->coaching->program_save([
            'name' => trim((string) $this->input->post('name')),
            'description' => trim((string) $this->input->post('description')),
            'total_price' => (float) $this->input->post('total_price'),
            'installment_count' => max(1, (int) $this->input->post('installment_count')),
            'status' => 'active',
        ], $id ?: null);
        $this->session->set_flashdata('success', 'Program saved.');
        redirect('coaching-billing');
    }

    public function create_invoice()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $client_id = (int) $this->input->post('coaching_client_id');
        $program_id = (int) $this->input->post('program_id');
        $program = $program_id ? $this->coaching->program_get($program_id) : null;
        $total = $program ? (float) $program->total_price : (float) $this->input->post('total_amount');
        $installments = $program ? (int) $program->installment_count : max(1, (int) $this->input->post('installment_count'));
        $invoice_id = $this->coaching->invoice_create_with_installments($client_id, $program_id, $total, $installments);
        $this->session->set_flashdata('success', 'Invoice created.');
        redirect('coaching-billing/invoice/' . $invoice_id);
    }

    public function invoice($id)
    {
        $invoice = $this->coaching->invoice_get($id);
        if (!$invoice) {
            show_404();
        }
        $this->load->view('coaching/billing/invoice', [
            'invoice' => $invoice,
            'installments' => $this->coaching->installments_for_invoice((int) $id),
            'client' => $this->coaching->client_get($invoice->coaching_client_id),
        ]);
    }

    public function mark_paid($installment_id)
    {
        $this->coaching->mark_installment_paid((int) $installment_id, 'manual', 'MAN-' . time());
        $this->session->set_flashdata('success', 'Installment marked paid.');
        redirect($this->input->get('redirect') ?: 'coaching-billing');
    }

    public function payouts()
    {
        if ($this->input->method() === 'post') {
            $this->coaching->payout_save([
                'coach_id' => (int) $this->input->post('coach_id'),
                'session_id' => $this->input->post('session_id') ? (int) $this->input->post('session_id') : null,
                'amount' => (float) $this->input->post('amount'),
                'payout_date' => $this->input->post('payout_date') ?: date('Y-m-d'),
                'status' => $this->input->post('status') ?: 'pending',
                'notes' => trim((string) $this->input->post('notes')),
            ]);
            $this->session->set_flashdata('success', 'Payout recorded.');
            redirect('coaching-billing/payouts');
            return;
        }
        $this->load->view('coaching/billing/payouts', [
            'rows' => $this->coaching->payouts_all(),
            'coaches' => $this->coaching->coaches_all(),
        ]);
    }
}
