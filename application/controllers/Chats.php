<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chats extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','group_filter','permission']);
        $this->load->library(['session','upload']);
        $this->load->model('Chat_model');
        $this->_ensure_auth();
        if (function_exists('has_module_access') && !has_module_access('chats')) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(403);
                echo json_encode(['ok' => false, 'error' => 'Access denied']);
                exit;
            }
            show_error('You do not have permission to access Chats.', 403);
        }
        $this->_ensure_schema();
    }

    private function _ensure_auth() {
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
            exit;
        }
    }

    private function _ensure_schema() {
        if ($this->session->userdata('chat_schema_ok')) return;
        $this->Chat_model->ensure_schema();
        $this->session->set_userdata('chat_schema_ok', true);
    }

    // GET /chats
    public function index() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        $conversations = $this->Chat_model->list_conversations($user_id, $filters);
        $users = $this->Chat_model->list_users_for_select($filters);
        $this->load->view('chats/index', [
            'conversations' => $conversations,
            'users' => $users,
        ]);
    }

    // GET /chats/app - Unified 3-pane chat UI
    public function app() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        $conversations = $this->Chat_model->list_conversations($user_id, $filters);
        $users = $this->Chat_model->list_users_for_select($filters);
        $open_id = (int)$this->input->get('open');
        $auto_call_id = (int)$this->input->get('call');
        $auto_accept = (int)$this->input->get('auto_accept');
        $this->load->view('chats/app', [
            'conversations' => $conversations,
            'users' => $users,
            'user_id' => $user_id,
            'open_id' => $open_id,
            'auto_call_id' => $auto_call_id,
            'auto_accept' => $auto_accept ? 1 : 0,
        ]);
    }

    // POST /chats/start-dm
    public function start_dm() {
        $email = trim((string)$this->input->post('email'));
        if (!$email) { $this->session->set_flashdata('error', 'Provide an email.'); redirect('chats'); return; }
        $user_id = (int)$this->session->userdata('user_id');
        $peer = $this->Chat_model->find_user_by_email($email);
        if (!$peer) { $this->session->set_flashdata('error', 'User not found.'); redirect('chats'); return; }
        if ((int)$peer->id === $user_id) { $this->session->set_flashdata('error', 'Cannot start DM with yourself.'); redirect('chats'); return; }
        $conv_id = $this->Chat_model->start_dm($user_id, (int)$peer->id);
        redirect('chats/conversation/'.$conv_id);
    }

    // POST /chats/create-group
    public function create_group() {
        $title = trim((string)$this->input->post('title'));
        $participant_ids = $this->input->post('participants'); // array of user ids
        $user_id = (int)$this->session->userdata('user_id');
        // RBAC: require chat grouping permission if helper exists
        if (function_exists('has_module_access') && !has_module_access('chatsgrouping')) {
            show_error('You do not have permission to create groups.', 403);
        }
        if (!$title || empty($participant_ids) || !is_array($participant_ids)) {
            $this->session->set_flashdata('error', 'Provide a title and select participants.');
            redirect('chats');
            return;
        }
        $conv_id = $this->Chat_model->create_group($user_id, $title, array_map('intval', $participant_ids));
        redirect('chats/conversation/'.$conv_id);
    }

    // Legacy: /chats/conversation/{id} now redirects to unified /chats/app
    public function conversation($id) {
        $id = (int)$id;
        $user_id = (int)$this->session->userdata('user_id');
        if (!$this->Chat_model->is_participant($id, $user_id)) { show_error('Forbidden', 403); }
        redirect('chats/app?open='.$id);
    }

    // POST /chats/send  (AJAX)
    public function send_message() {
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id = (int)$this->session->userdata('user_id');
        if (!$this->Chat_model->is_participant($conversation_id, $user_id)) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        $body = trim((string)$this->input->post('body'));
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $attachment_path = null;
        if ($body === '' && empty($_FILES['attachment']['name'])) {
            $this->_json(['ok'=>false,'error'=>'Message body or attachment is required.']);
            return;
        }
        if (!empty($_FILES['attachment']['name'])) {
            $upload_path = FCPATH.'uploads/chats/';
            if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
            $config = [
                'upload_path'   => $upload_path,
                'allowed_types' => 'gif|jpg|jpeg|png|webp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|csv|zip|rar|mp4|mp3|ogg|wav',
                'max_size'      => 10240,
                'encrypt_name'  => true,
            ];
            $this->upload->initialize($config);
            if ($this->upload->do_upload('attachment')) {
                $up = $this->upload->data();
                $attachment_path = 'uploads/chats/'.$up['file_name'];
            } else {
                $this->_json(['ok'=>false,'error'=>$this->upload->display_errors('', '')]);
                return;
            }
        }
        $msg_id = $this->Chat_model->add_message($conversation_id, $user_id, $body, $attachment_path);
        $message = $this->Chat_model->get_message_by_id($msg_id);
        $this->_json(['ok'=>true,'message_id'=>$msg_id,'message'=>$message]);
    }

    // GET /chats/fetch?conversation_id=1&since_id=10 (AJAX)
    public function fetch_messages() {
        $conversation_id = (int)$this->input->get('conversation_id');
        $since_id = (int)$this->input->get('since_id');
        $user_id = (int)$this->session->userdata('user_id');
        if (!$this->Chat_model->is_participant($conversation_id, $user_id)) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        $rows = $this->Chat_model->fetch_messages($conversation_id, $since_id);
        $this->_json(['ok'=>true,'messages'=>$rows]);
    }

    // POST /chats/add-participants
    public function add_participants() {
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_ids = $this->input->post('user_ids');
        $actor = (int)$this->session->userdata('user_id');
        if (function_exists('has_module_access') && !has_module_access('chatsgrouping')) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        if (!$this->Chat_model->is_participant($conversation_id, $actor)) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        $this->Chat_model->add_participants($conversation_id, array_map('intval', (array)$user_ids));
        $this->_json(['ok'=>true]);
    }

    // POST /chats/remove-participant
    public function remove_participant() {
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id = (int)$this->input->post('user_id');
        $actor = (int)$this->session->userdata('user_id');
        if (function_exists('has_module_access') && !has_module_access('chatsgrouping')) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        if (!$this->Chat_model->is_participant($conversation_id, $actor)) { $this->_json(['ok'=>false,'error'=>'forbidden']); return; }
        $this->Chat_model->remove_participant($conversation_id, (int)$user_id);
        $this->_json(['ok'=>true]);
    }

    // GET or POST /chats/typing — unified dispatcher used by JS
    public function typing() {
        if ($this->input->method() === 'post') {
            $this->set_typing();
        } else {
            $this->get_typing();
        }
    }

    // GET or POST /chats/online-status — unified dispatcher used by JS
    public function online_status() {
        if ($this->input->method() === 'post') {
            $this->set_online_status();
        } else {
            $this->get_online_status();
        }
    }

    // POST /chats/typing (AJAX)
    public function set_typing() {
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id = (int)$this->session->userdata('user_id');
        $is_typing = (bool)$this->input->post('is_typing');
        
        if (!$this->Chat_model->is_participant($conversation_id, $user_id)) {
            $this->_json(['ok'=>false,'error'=>'forbidden']);
            return;
        }
        
        $this->Chat_model->set_typing($conversation_id, $user_id, $is_typing);
        $this->_json(['ok'=>true]);
    }

    // GET /chats/typing?conversation_id=1 (AJAX)
    public function get_typing() {
        $conversation_id = (int)$this->input->get('conversation_id');
        $user_id = (int)$this->session->userdata('user_id');
        
        if (!$this->Chat_model->is_participant($conversation_id, $user_id)) {
            $this->_json(['ok'=>false,'error'=>'forbidden']);
            return;
        }
        
        $typing_users = $this->Chat_model->get_typing_users($conversation_id, $user_id);
        $this->_json(['ok'=>true,'typing_users'=>$typing_users]);
    }

    // POST /chats/online-status (AJAX)
    public function set_online_status() {
        $user_id = (int)$this->session->userdata('user_id');
        $is_online = (bool)$this->input->post('is_online');
        
        $this->Chat_model->set_online_status($user_id, $is_online);
        $this->_json(['ok'=>true]);
    }

    // GET /chats/online-status?user_ids=1,2,3 (AJAX)
    public function get_online_status() {
        $user_ids = $this->input->get('user_ids');
        if ($user_ids) {
            $user_ids = array_map('intval', explode(',', $user_ids));
            $status_data = $this->Chat_model->get_online_status($user_ids);
            $this->_json(['ok'=>true,'status'=>$status_data]);
        } else {
            $this->_json(['ok'=>false,'error'=>'missing user_ids']);
        }
    }

    // POST /chats/reaction (AJAX)
    public function add_reaction() {
        $message_id = (int)$this->input->post('message_id');
        $user_id = (int)$this->session->userdata('user_id');
        $reaction = trim($this->input->post('reaction'));
        
        if (!$message_id || !$reaction) {
            $this->_json(['ok'=>false,'error'=>'missing parameters']);
            return;
        }
        
        // Check if user is participant in the conversation
        $this->db->select('conversation_id');
        $this->db->from('messages');
        $this->db->where('id', $message_id);
        $message = $this->db->get()->row();
        
        if (!$message || !$this->Chat_model->is_participant($message->conversation_id, $user_id)) {
            $this->_json(['ok'=>false,'error'=>'forbidden']);
            return;
        }
        
        $this->Chat_model->add_reaction($message_id, $user_id, $reaction);
        $reactions = $this->Chat_model->get_message_reactions($message_id);
        $this->_json(['ok'=>true,'reactions'=>$reactions]);
    }

    // POST /chats/reaction/remove (AJAX)
    public function remove_reaction() {
        $message_id = (int)$this->input->post('message_id');
        $user_id = (int)$this->session->userdata('user_id');
        $reaction = trim($this->input->post('reaction'));
        
        if (!$message_id || !$reaction) {
            $this->_json(['ok'=>false,'error'=>'missing parameters']);
            return;
        }
        
        // Check if user is participant in the conversation
        $this->db->select('conversation_id');
        $this->db->from('messages');
        $this->db->where('id', $message_id);
        $message = $this->db->get()->row();
        
        if (!$message || !$this->Chat_model->is_participant($message->conversation_id, $user_id)) {
            $this->_json(['ok'=>false,'error'=>'forbidden']);
            return;
        }
        
        $this->Chat_model->remove_reaction($message_id, $user_id, $reaction);
        $reactions = $this->Chat_model->get_message_reactions($message_id);
        $this->_json(['ok'=>true,'reactions'=>$reactions]);
    }

    // GET /chats/reactions?message_id=1 (AJAX)
    public function get_reactions() {
        $message_id = (int)$this->input->get('message_id');
        $user_id = (int)$this->session->userdata('user_id');
        
        if (!$message_id) {
            $this->_json(['ok'=>false,'error'=>'missing message_id']);
            return;
        }
        
        // Check if user is participant in the conversation
        $this->db->select('conversation_id');
        $this->db->from('messages');
        $this->db->where('id', $message_id);
        $message = $this->db->get()->row();
        
        if (!$message || !$this->Chat_model->is_participant($message->conversation_id, $user_id)) {
            $this->_json(['ok'=>false,'error'=>'forbidden']);
            return;
        }
        
        $reactions = $this->Chat_model->get_message_reactions($message_id);
        $user_reaction = $this->Chat_model->get_user_reaction($message_id, $user_id);
        $this->_json(['ok'=>true,'reactions'=>$reactions,'user_reaction'=>$user_reaction]);
    }

    // POST /chats/delete-message (AJAX)
    public function delete_message() {
        if ($this->input->method() !== 'post') { $this->_json(['ok'=>false,'error'=>'POST required']); return; }
        $message_id = (int)$this->input->post('message_id');
        $user_id    = (int)$this->session->userdata('user_id');
        $is_admin   = ((int)$this->session->userdata('role_id') === 1) || (function_exists('is_admin_group') && is_admin_group());

        $msg = $this->Chat_model->get_message_by_id($message_id);
        if (!$msg) { $this->_json(['ok'=>false,'error'=>'Message not found']); return; }

        // Only sender or admin can delete
        if ((int)$msg->sender_id !== $user_id && !$is_admin) {
            $this->_json(['ok'=>false,'error'=>'forbidden']); return;
        }

        $this->Chat_model->delete_message($message_id);
        $this->_json(['ok'=>true]);
    }

    // POST /chats/edit-message (AJAX)
    public function edit_message() {
        if ($this->input->method() !== 'post') { $this->_json(['ok'=>false,'error'=>'POST required']); return; }
        $message_id = (int)$this->input->post('message_id');
        $user_id    = (int)$this->session->userdata('user_id');
        $new_body   = trim((string)$this->input->post('body'));

        if (empty($new_body)) { $this->_json(['ok'=>false,'error'=>'Body cannot be empty']); return; }

        $msg = $this->Chat_model->get_message_by_id($message_id);
        if (!$msg) { $this->_json(['ok'=>false,'error'=>'Message not found']); return; }

        // Only sender can edit their own messages
        if ((int)$msg->sender_id !== $user_id) {
            $this->_json(['ok'=>false,'error'=>'forbidden']); return;
        }

        $new_body = htmlspecialchars($new_body, ENT_QUOTES, 'UTF-8');
        $this->Chat_model->edit_message($message_id, $new_body);
        $updated = $this->Chat_model->get_message_by_id($message_id);
        $this->_json(['ok'=>true,'message'=>$updated]);
    }

    private function _json($arr) {
        $this->output->set_content_type('application/json')->set_output(json_encode($arr));
    }
}
