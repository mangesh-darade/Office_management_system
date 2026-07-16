<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Calendar email reminders (CodeIgniter / PHP).
 */
class Calendar_reminders extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'form_validation', 'google_calendar_lib']);
        require_module_access(['calendar_reminders'], true);
    }

    /**
     * GET /calendar-reminders
     */
    public function index()
    {
        $this->load->view('calendar_reminders/index', [
            'configured' => $this->google_calendar_lib->is_configured(),
            'connected' => $this->google_calendar_lib->is_connected(),
            'redirect_uri' => $this->google_calendar_lib->get_redirect_uri(),
        ]);
    }

    /**
     * POST /calendar-reminders/create
     */
    public function create()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
        $this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[200]');
        $this->form_validation->set_rules('when', 'Date and time', 'required|trim');
        $this->form_validation->set_rules('minutes', 'Remind before', 'required|integer|is_natural');
        $this->form_validation->set_rules('description', 'Note', 'trim|max_length[2000]');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors(' ', ' '));
            redirect('calendar-reminders');
        }

        if (!$this->google_calendar_lib->is_connected()) {
            $this->session->set_flashdata('error', 'Connect Google Calendar first (Settings).');
            redirect('calendar-reminders');
        }

        $when_raw = trim((string) $this->input->post('when', true));
        // datetime-local: 2026-07-16T10:00
        $when_normalized = str_replace('T', ' ', $when_raw);
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $when_normalized)) {
            $this->session->set_flashdata('error', 'Invalid date/time format.');
            redirect('calendar-reminders');
        }

        $result = $this->google_calendar_lib->create_reminder_event([
            'email' => trim((string) $this->input->post('email', true)),
            'title' => trim((string) $this->input->post('title', true)),
            'when' => $when_normalized . ':00',
            'reminder_minutes' => (int) $this->input->post('minutes'),
            'description' => trim((string) $this->input->post('description', true)),
            'timezone' => 'Asia/Kolkata',
        ]);

        if (empty($result['ok'])) {
            $this->session->set_flashdata(
                'error',
                !empty($result['message']) ? $result['message'] : 'Failed to create reminder.'
            );
            redirect('calendar-reminders');
        }

        $msg = $result['message'];
        if (!empty($result['data']['htmlLink'])) {
            $msg .= ' Open: ' . $result['data']['htmlLink'];
        }
        $this->session->set_flashdata('success', $msg);
        redirect('calendar-reminders');
    }

    /**
     * GET|POST /calendar-reminders/settings
     */
    public function settings()
    {
        if ($this->input->method() === 'post') {
            $client_id = trim((string) $this->input->post('client_id', true));
            $client_secret = trim((string) $this->input->post('client_secret', true));
            $json_raw = (string) $this->input->post('credentials_json');

            $result = null;
            if (trim($json_raw) !== '') {
                $result = $this->google_calendar_lib->import_credentials_json($json_raw);
            } elseif ($client_id !== '' && $client_secret !== '') {
                $saved = $this->google_calendar_lib->save_credentials($client_id, $client_secret);
                $result = $saved
                    ? ['ok' => true, 'message' => 'Credentials saved. Click Connect Google to authorize.']
                    : ['ok' => false, 'message' => 'Could not save credentials.'];
            } else {
                $result = ['ok' => false, 'message' => 'Provide Client ID + Secret, or paste credentials.json content.'];
            }

            if (empty($result['ok'])) {
                $this->session->set_flashdata('error', $result['message']);
                redirect('calendar-reminders/settings');
            }

            if (!empty($result['warning'])) {
                $this->session->set_flashdata('error', $result['warning']);
            } else {
                $this->session->set_flashdata('success', $result['message']);
            }
            redirect('calendar-reminders/settings');
        }

        $this->load->view('calendar_reminders/settings', [
            'configured' => $this->google_calendar_lib->is_configured(),
            'connected' => $this->google_calendar_lib->is_connected(),
            'redirect_uri' => $this->google_calendar_lib->get_redirect_uri(),
        ]);
    }

    /**
     * GET /calendar-reminders/connect
     */
    public function connect()
    {
        if (!$this->google_calendar_lib->is_configured()) {
            $this->session->set_flashdata('error', 'Save Google Client ID and Secret first.');
            redirect('calendar-reminders/settings');
        }

        $url = $this->google_calendar_lib->get_auth_url();
        if ($url === '') {
            $this->session->set_flashdata('error', 'Could not build Google auth URL.');
            redirect('calendar-reminders/settings');
        }

        redirect($url);
    }

    /**
     * GET /calendar-reminders/oauth-callback
     */
    public function oauth_callback()
    {
        $error = trim((string) $this->input->get('error', true));
        if ($error !== '') {
            $this->session->set_flashdata('error', 'Google authorization denied.');
            redirect('calendar-reminders/settings');
        }

        $code = trim((string) $this->input->get('code', true));
        $result = $this->google_calendar_lib->handle_oauth_callback($code);

        if (empty($result['ok'])) {
            $this->session->set_flashdata(
                'error',
                !empty($result['message']) ? $result['message'] : 'OAuth failed.'
            );
            redirect('calendar-reminders/settings');
        }

        $this->session->set_flashdata('success', $result['message']);
        redirect('calendar-reminders');
    }

    /**
     * POST /calendar-reminders/disconnect
     */
    public function disconnect()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->google_calendar_lib->disconnect();
        $this->session->set_flashdata('success', 'Google Calendar disconnected.');
        redirect('calendar-reminders/settings');
    }
}
