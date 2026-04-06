<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class External_training extends CI_Controller
{
    private function can_any_external_training()
    {
        if (!function_exists('has_module_access')) {
            return true;
        }
        return has_module_access('external_training')
            || has_module_access('external_training_watch')
            || has_module_access('external_training_list')
            || has_module_access('external_training_add')
            || has_module_access('external_training_edit')
            || has_module_access('external_training_delete');
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->model('External_training_model', 'ext');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'permission'));

        // In-app player is available to any logged-in user (see watch()).
        $method = $this->router->fetch_method();
        if ($method === 'watch') {
            return;
        }

        // Base gate: user must have at least one external training permission.
        if (function_exists('require_module_access') && !$this->can_any_external_training()) {
            require_module_access('external_training', true);
        }
    }

    public function index()
    {
        if (function_exists('require_module_access')) {
            require_module_access(array(
                'external_training',
                'external_training_watch',
                'external_training_list',
                'external_training_add',
                'external_training_edit',
                'external_training_delete',
            ), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $data['rows'] = $this->ext->all();
        $this->load->view('external_training/index', $data);
    }

    /**
     * Logged-in-only in-app playback (iframe). Inactive items: only users who can edit may preview.
     */
    public function watch($id)
    {
        if (!(int) $this->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }
        if (function_exists('require_module_access')) {
            require_module_access(array(
                'external_training_watch',
                'external_training',
                'external_training_list',
                'external_training_add',
                'external_training_edit',
                'external_training_delete',
            ), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $row = $this->ext->get((int) $id);
        if (!$row) {
            show_404();
        }
        $active = (int) $row->is_active === 1;
        if (!$active) {
            $canPreview = function_exists('has_module_access')
                && (has_module_access('external_training') || has_module_access('external_training_edit'));
            if (!$canPreview) {
                show_404();
            }
        }
        $embed = trim((string) $row->embed_code);
        if ($embed === '') {
            show_error('This training has no embed content.', 400);
        }
        $data['row'] = $row;
        $norm = $this->normalize_embed_for_watch($embed);
        $data['embed_mode'] = $norm['mode'];
        $data['embed_url'] = '';
        // html = pasted markup; iframe = third-party URL; video = direct file (we can restrict right-click / download UI)
        $data['player'] = 'html';
        if ($norm['mode'] === 'url' && $norm['url'] !== '') {
            if ($this->is_direct_video_file_url($norm['url'])) {
                $data['player'] = 'video';
                $data['embed_url'] = $norm['url'];
            } else {
                $data['player'] = 'iframe';
                $data['embed_url'] = $this->append_youtube_embed_params($norm['url']);
            }
        }
        $this->load->view('external_training/watch', $data);
    }

    /**
     * Prefer a single controlled iframe URL (same tab, inside app shell).
     * Converts common watch links to embed URLs and pulls src from pasted HTML.
     *
     * @return array{mode:string,url:string}
     */
    private function normalize_embed_for_watch($embed)
    {
        $embed = trim((string) $embed);
        $out = array('mode' => 'html', 'url' => '');

        if ($embed === '') {
            return $out;
        }

        // Plain URL only (no HTML)
        $plain = trim(strip_tags($embed));
        if ($plain === $embed && preg_match('#\Ahttps?://\S+\z#i', $embed)) {
            $out['mode'] = 'url';
            $out['url'] = $this->normalize_stream_url($embed);
            return $out;
        }

        // <iframe src="...">
        if (preg_match('/<iframe[^>]*\ssrc\s*=\s*("|\')(.+?)\1/is', $embed, $m)) {
            $url = html_entity_decode(trim($m[2]), ENT_QUOTES, 'UTF-8');
            if ($this->looks_like_http_url($url)) {
                $out['mode'] = 'url';
                $out['url'] = $this->normalize_stream_url($url);
                return $out;
            }
        }
        if (preg_match('/<iframe[^>]*\ssrc\s*=\s*([^\s>]+)/i', $embed, $m)) {
            $url = html_entity_decode(trim($m[1], '"\''), ENT_QUOTES, 'UTF-8');
            if ($this->looks_like_http_url($url)) {
                $out['mode'] = 'url';
                $out['url'] = $this->normalize_stream_url($url);
                return $out;
            }
        }

        // <embed src="...">
        if (preg_match('/<embed[^>]*\ssrc\s*=\s*("|\')(.+?)\1/is', $embed, $m)) {
            $url = html_entity_decode(trim($m[2]), ENT_QUOTES, 'UTF-8');
            if ($this->looks_like_http_url($url)) {
                $out['mode'] = 'url';
                $out['url'] = $this->normalize_stream_url($url);
                return $out;
            }
        }

        return $out;
    }

    private function looks_like_http_url($url)
    {
        return (bool) preg_match('#\Ahttps?://#i', trim((string) $url));
    }

    /** Map watch/share links to embed-friendly URLs where possible. */
    private function normalize_stream_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }

        // youtu.be/<id>
        if (preg_match('#\Ahttps?://(?:www\.)?youtu\.be/([a-zA-Z0-9_-]{6,})#i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        $parts = @parse_url($url);
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $host = strtolower($parts['host']);
            // youtube.com/watch?v=
            if (preg_match('/(^|\.)youtube\.com$/i', $host) && !empty($parts['query'])) {
                parse_str($parts['query'], $q);
                if (!empty($q['v']) && preg_match('/^[a-zA-Z0-9_-]{6,}$/', $q['v'])) {
                    $embed = 'https://www.youtube.com/embed/' . $q['v'];
                    if (!empty($q['t'])) {
                        $t = preg_replace('/\D+/', '', (string) $q['t']);
                        if ($t !== '') {
                            $embed .= '?start=' . $t;
                        }
                    }
                    return $embed;
                }
            }
            // vimeo.com/<id>
            if (preg_match('/(^|\.)vimeo\.com$/i', $host) && !empty($parts['path'])) {
                if (preg_match('#/(\d{6,})#', (string) $parts['path'], $m)) {
                    return 'https://player.vimeo.com/video/' . $m[1];
                }
            }
        }

        return $url;
    }

    /** True when URL path looks like a file we can play in an HTML5 video element (enables in-page copy/download limits). */
    private function is_direct_video_file_url($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }
        return (bool) preg_match('/\.(mp4|webm|ogv|ogg|m4v|mov)\s*$/i', $path);
    }

    /** Extra YouTube embed query flags (does not remove their context menu — browser security). */
    private function append_youtube_embed_params($url)
    {
        if (!preg_match('#^https?://(?:www\.)?youtube\.com/embed/#i', $url)) {
            return $url;
        }
        $parts = @parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host']) || empty($parts['path'])) {
            return $url;
        }
        $q = array();
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
        }
        if (!array_key_exists('modestbranding', $q)) {
            $q['modestbranding'] = '1';
        }
        if (!array_key_exists('rel', $q)) {
            $q['rel'] = '0';
        }
        $rebuilt = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        $nq = http_build_query($q);
        if ($nq !== '') {
            $rebuilt .= '?' . $nq;
        }
        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }
        return $rebuilt;
    }

    public function create()
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_add'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $data['row'] = null;
        $this->load->view('external_training/form', $data);
    }

    public function edit($id)
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_edit'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        $row = $this->ext->get((int) $id);
        if (!$row) {
            show_404();
        }
        $data['row'] = $row;
        $this->load->view('external_training/form', $data);
    }

    public function save()
    {
        $id = (int) $this->input->post('id');
        if (function_exists('require_module_access')) {
            if ($id > 0) {
                require_module_access(array('external_training', 'external_training_edit'), true);
            } else {
                require_module_access(array('external_training', 'external_training_add'), true);
            }
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $name = trim((string) $this->input->post('name'));
        $embed_code = trim((string) $this->input->post('embed_code'));
        $is_active = $this->input->post('is_active') ? 1 : 0;

        if ($name === '' || $embed_code === '') {
            $this->session->set_flashdata('error', 'Name and Embed Code are required.');
            if ($id) {
                redirect('external-training/edit/' . $id);
            } else {
                redirect('external-training/create');
            }
            return;
        }

        $row = array(
            'name' => $name,
            'embed_code' => $embed_code,
            'is_active' => $is_active,
        );

        if ($id) {
            $this->ext->update($id, $row);
            $this->session->set_flashdata('success', 'External training updated.');
        } else {
            $this->ext->insert($row);
            $this->session->set_flashdata('success', 'External training created.');
        }
        redirect('external-training');
    }

    public function delete($id)
    {
        if (function_exists('require_module_access')) {
            require_module_access(array('external_training', 'external_training_delete'), true);
        }
        if (!$this->ext->schema_ready()) {
            show_error('Schema not installed for sma_external_trainings.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->ext->delete((int) $id);
        $this->session->set_flashdata('success', 'External training deleted.');
        redirect('external-training');
    }
}

