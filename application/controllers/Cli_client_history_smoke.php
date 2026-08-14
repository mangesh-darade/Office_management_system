<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quick smoke for client history filters/attachments + logo path format.
 * Run: php index.php cli_client_history_smoke run
 */
class Cli_client_history_smoke extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_error('CLI only', 403);
        }
        $this->load->model('Client_model', 'clients');
        $this->load->helper(array('company', 'view_output'));
    }

    public function run()
    {
        $pass = 0;
        $fail = 0;
        $ok = function ($cond, $msg) use (&$pass, &$fail) {
            if ($cond) {
                echo "OK  {$msg}\n";
                $pass++;
            } else {
                echo "FAIL {$msg}\n";
                $fail++;
            }
        };

        echo "-- Logo path --\n";
        $logo = (string) get_company_logo();
        $ok($logo === '' || strpos($logo, 'uploads/') === 0, 'company_logo empty or starts with uploads/ got=' . $logo);
        $login_bug = ($logo !== '' && strpos($logo, 'uploads/uploads/') === 0);
        $ok(!$login_bug, 'logo path is not double-uploads');

        $src = file_get_contents(APPPATH . 'views/auth/login.php');
        $ok(strpos($src, "base_url('uploads/'.\$settings['company_logo'])") === false, 'login view does not double-prefix uploads/');
        $ok(strpos($src, "base_url(\$settings['company_logo'])") !== false, 'login uses base_url(company_logo)');

        $quote = file_get_contents(APPPATH . 'helpers/subscription_builder_quote_helper.php');
        $prefer_pos = strpos($quote, 'Prefer Settings');
        $get_pos = strpos($quote, 'get_company_logo()');
        $cand_pos = strpos($quote, 'sateri-digital-logo-quote-header.png');
        $ok($prefer_pos !== false && $get_pos !== false && $get_pos < $cand_pos, 'quote helper prefers company logo before hardcoded branding');

        echo "-- Client history methods --\n";
        $ok(method_exists($this->clients, 'list_history'), 'list_history exists');
        $ok(method_exists($this->clients, 'get_history_attachment'), 'get_history_attachment exists');

        $uid = 1;
        $id = (int) $this->clients->create_client(array(
            'client_code' => 'SMK-H-' . date('YmdHis'),
            'company_name' => 'Smoke Hist ' . date('His'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $ok($id > 0, 'create_client id=' . $id);
        if ($id > 0) {
            $html = sanitize_html_output('<p><strong>Bold</strong> note <script>alert(1)</script></p>');
            $ok(strpos($html, '<script>') === false, 'sanitize strips script');
            $ok(strpos($html, '<strong>') !== false, 'sanitize keeps strong');

            $this->clients->log_activity($id, $uid, 'note', null, array(
                'detail' => $html,
                'comment' => 'Bold note',
                'is_html' => 1,
                'attachments' => array(
                    array(
                        'path' => 'uploads/clients/history/' . $id . '/smoke.txt',
                        'original_name' => 'smoke.txt',
                        'size' => 12,
                    ),
                ),
            ));

            $dir = FCPATH . 'uploads/clients/history/' . $id . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($dir . 'smoke.txt', 'hello smoke');

            $all = $this->clients->list_history($id);
            $ok(count($all) >= 1, 'list_history count>=1 got=' . count($all));
            $note = null;
            foreach ($all as $h) {
                if ((string) $h->action === 'note') {
                    $note = $h;
                    break;
                }
            }
            $ok($note !== null, 'note row present');
            $ok($note && !empty($note->attachments) && count($note->attachments) === 1, 'note has 1 attachment');
            $ok($note && strpos((string) $note->detail, '<strong>') !== false, 'note detail keeps html');

            $filtered = $this->clients->list_history($id, array('q' => 'Bold'));
            $ok(count($filtered) >= 1, 'filter q=Bold matches');
            $filtered2 = $this->clients->list_history($id, array('action' => 'note'));
            $ok(count($filtered2) >= 1, 'filter action=note matches');
            $filtered3 = $this->clients->list_history($id, array('action' => 'created'));
            $ok(is_array($filtered3), 'filter action=created returns array');

            $att = $this->clients->get_history_attachment($id, (int) $note->id, 0);
            $ok($att && $att->original_name === 'smoke.txt', 'get_history_attachment name');
            $ok($att && is_file(FCPATH . $att->path), 'attachment file exists');

            $routes = file_get_contents(APPPATH . 'config/routes.php');
            $ok(strpos($routes, 'clients/history-attachment') !== false, 'history-attachment route exists');

            $view = file_get_contents(APPPATH . 'views/clients/view.php');
            $ok(strpos($view, "allowed_tabs = array('overview', 'requirements', 'tasks', 'defects', 'history')") !== false
                || strpos($view, "'history'") !== false && strpos($view, 'history-tab') !== false, 'history tab in view');
            $ok(strpos($view, '>Attachment</th>') !== false, 'Attachment column in history table');
            $ok(strpos($view, 'tinymce@6.8.3') !== false, 'TinyMCE loaded for history note');

            $this->clients->delete_client($id);
            @unlink($dir . 'smoke.txt');
            @rmdir($dir);
        }

        echo "\n=== Summary: {$pass} passed, {$fail} failed ===\n";
        exit($fail > 0 ? 1 : 0);
    }
}
