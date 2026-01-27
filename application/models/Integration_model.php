<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Integration_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // --- SLACK INTEGRATION ---
    
    /**
     * Send Slack Notification
     * @param string $webhook_url
     * @param string $message
     */
    public function send_slack_message($webhook_url, $message, $channel = null) {
        $data = ['text' => $message];
        if ($channel) $data['channel'] = $channel;

        $payload = json_encode($data);
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }

    // --- NATIVE CHAT INTEGRATION ---

    /**
     * Generate Link to Start a Video Call
     * Since we have a native module, we don't need Zoom.
     * This generates a link that:
     * 1. Opens the chat app
     * 2. Focuses the conversation
     * 3. Auto-starts the call
     */
    public function get_chat_call_link($conversation_id) {
        return site_url("chats/app?open=$conversation_id&call=1&auto_accept=1");
    }

    // --- GOOGLE CALENDAR (iCal Export) ---

    /**
     * Generate iCal Content for generic calendar sync
     */
    public function generate_ical_feed($events) {
        $output = "BEGIN:VCALENDAR\r\n";
        $output .= "VERSION:2.0\r\n";
        $output .= "PRODID:-//Office Management System//NONSGML v1.0//EN\r\n";
        $output .= "CALSCALE:GREGORIAN\r\n";
        
        foreach ($events as $event) {
            $output .= "BEGIN:VEVENT\r\n";
            $output .= "UID:" . md5($event['id']) . "@yourdomain.com\r\n";
            $output .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $output .= "DTSTART:" . $this->_format_ical_date($event['start']) . "\r\n";
            $output .= "DTEND:" . $this->_format_ical_date($event['end']) . "\r\n";
            $output .= "SUMMARY:" . $this->_escape_ical($event['title']) . "\r\n";
            $output .= "DESCRIPTION:" . $this->_escape_ical($event['description']) . "\r\n";
            $output .= "END:VEVENT\r\n";
        }
        
        $output .= "END:VCALENDAR";
        return $output;
    }

    private function _format_ical_date($timestamp) {
        return gmdate('Ymd\THis\Z', strtotime($timestamp));
    }

    private function _escape_ical($text) {
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace(",", "\,", $text);
        $text = str_replace(";", "\;", $text);
        $text = str_replace("\n", "\\n", $text);
        return $text;
    }
}
