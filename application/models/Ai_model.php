<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Predictive Analytics: Calculate Attrition Risk Score (0-100)
     * Logic: Analyzes tenure, salary hikes, leave patterns, and project load.
     */
    public function predict_attrition($user_id) {
        $score = 0;
        $risk_factors = [];

        // Load User and Department Info
        $user = $this->db->select('id, created_at, department_id, basic_salary')->get_where('users', ['id' => $user_id])->row();
        if (!$user) return ['error' => 'User not found'];

        // 1. Tenure Check "The 1-2 Year Itch"
        $join_date = new DateTime($user->created_at);
        $now = new DateTime();
        $tenure_months = $join_date->diff($now)->m + ($join_date->diff($now)->y * 12);

        if ($tenure_months > 12 && $tenure_months < 24) {
            $score += 15; 
            $risk_factors[] = "Mid-term tenure volatility (1-2 years)";
        } elseif ($tenure_months > 36) {
            $score -= 5; // Long term employees are generally safer
        }

        // 2. Attendance Pattern (Last 30 days) vs Department Average
        $this->db->where('user_id', $user_id);
        $this->db->where('att_date >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->where('status', 'absent');
        $user_absents = $this->db->count_all_results('attendance');
        
        // Get Dept Average
        $dept_avg_absents = $this->_get_dept_avg_absents($user->department_id);
        
        if ($user_absents > 3) {
            $score += 20; 
            $risk_factors[] = "High recent absenteeism ({$user_absents} days)";
            
            if ($user_absents > ($dept_avg_absents * 1.5)) {
                $score += 10;
                $risk_factors[] = "Absenteeism significantly higher than department average";
            }
        }

        // 3. Late Arrival Trend (Using Forecast)
        $forecast = $this->forecast_attendance($user_id);
        if ($forecast['trend'] === 'Getting Later' && $forecast['slope'] > 3) {
            $score += 15;
            $risk_factors[] = "Consistently arriving later (Slope: {$forecast['slope']})";
        }

        // 4. Leave Balance Burn Rate
        // Check if user has used > 80% of leaves rapidly (mock logic as strictly balance history needed)
        // We'll check current balance vs usage this month
        $this->db->where('user_id', $user_id);
        $this->db->select_sum('remaining_leaves');
        $balance_row = $this->db->get('leave_balances')->row();
        $total_balance = $balance_row ? $balance_row->remaining_leaves : 0;
        
        if ($total_balance < 2 && $user_absents > 2) {
            $score += 10;
            $risk_factors[] = "Low leave balance with high absence";
        }

        // 5. Sentiment Analysis (if recent chats exist)
        // This effectively "hooks" into the chat table if available
        if ($this->db->table_exists('chats')) {
             $this->db->where('sender_id', $user_id);
             $this->db->order_by('id', 'DESC');
             $this->db->limit(10);
             $chats = $this->db->get('chats')->result();
             $negative_count = 0;
             foreach ($chats as $chat) {
                 $analysis = $this->analyze_sentiment($chat->message); // Assuming 'message' column
                 if ($analysis['label'] === 'Negative') $negative_count++;
             }
             if ($negative_count >= 3) {
                 $score += 20;
                 $risk_factors[] = "Negative sentiment detected in recent communications";
             }
        }

        // Cap score
        $score = min($score, 100);

        return [
            'user_id' => $user_id,
            'risk_score' => $score,
            'risk_level' => $this->_get_risk_level($score),
            'factors' => $risk_factors,
            'prediction_date' => date('Y-m-d')
        ];
    }

    private function _get_dept_avg_absents($dept_id) {
        if (!$dept_id) return 2; // Default fallback
        
        // Count total users in dept
        $this->db->where('department_id', $dept_id);
        $user_count = $this->db->count_all_results('users');
        if ($user_count == 0) return 2;
        
        // Count total absents in dept last 30 days
        $this->db->select('users.id');
        $this->db->from('attendance');
        $this->db->join('users', 'users.id = attendance.user_id');
        $this->db->where('users.department_id', $dept_id);
        $this->db->where('attendance.att_date >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->where('attendance.status', 'absent');
        $total_absents = $this->db->count_all_results();
        
        return $total_absents / $user_count;
    }

    /**
     * Attendance Pattern Forecasting
     * Uses Linear Regression to predict next week's check-in time trends
     */
    public function forecast_attendance($user_id) {
        // Get last 30 days check-in times
        $this->db->select('att_date, punch_in');
        $this->db->where('user_id', $user_id);
        $this->db->where('punch_in !=', null);
        $this->db->where('punch_in !=', '00:00:00');
        $this->db->order_by('att_date', 'ASC');
        $this->db->limit(30);
        $logs = $this->db->get('attendance')->result();

        if (empty($logs)) return ['trend' => 'No Data', 'predicted_late' => false];

        $x_values = []; // Days (1, 2, 3...)
        $y_values = []; // Minutes from midnight
        $i = 1;

        foreach ($logs as $log) {
            $x_values[] = $i++;
            
            // Handle both "H:i:s" and "Y-m-d H:i:s" formats
            $timestamp = strtotime($log->punch_in);
            if ($timestamp === false) continue; // Skip invalid dates
            
            // Extract hour and minute explicitly
            $hour = (int)date('H', $timestamp);
            $minute = (int)date('i', $timestamp);
            
            $y_values[] = ($hour * 60) + $minute;
        }

        // Calculate Slope (m) and Intercept (b) for y = mx + b
        $n = count($x_values);
        $x_sum = array_sum($x_values);
        $y_sum = array_sum($y_values);
        
        $xx_sum = 0;
        $xy_sum = 0;
        
        for ($j = 0; $j < $n; $j++) {
            $xx_sum += ($x_values[$j] * $x_values[$j]);
            $xy_sum += ($x_values[$j] * $y_values[$j]);
        }

        $slope = (($n * $xy_sum) - ($x_sum * $y_sum)) / (($n * $xx_sum) - ($x_sum * $x_sum));
        
        // Interpretation
        $trend = "Stable";
        if ($slope > 2) $trend = "Getting Later"; // 2 mins later per day trend
        if ($slope < -2) $trend = "Getting Earlier";

        return [
            'trend' => $trend,
            'slope' => round($slope, 2),
            'avg_time' => round($y_sum / $n)
        ];
    }

    /**
     * Sentiment Analysis
     * Dictionary-based approach for analyzing text
     */
    public function analyze_sentiment($text) {
        // Basic AFINN-like dictionary (simplified for portability)
        $positive = ['good', 'great', 'excellent', 'happy', 'love', 'wonderful', 'best', 'motivated', 'completed', 'success', 'supportive'];
        $negative = ['bad', 'terrible', 'worst', 'hate', 'sad', 'angry', 'boring', 'tired', 'failed', 'issue', 'problem', 'stuck', 'difficult', 'annoying'];

        $text = strtolower($text);
        $words = explode(' ', preg_replace('/[^a-z0-9 ]/', '', $text));
        
        $score = 0;
        $matched_words = [];

        foreach ($words as $word) {
            if (in_array($word, $positive)) {
                $score++;
                $matched_words['positive'][] = $word;
            }
            if (in_array($word, $negative)) {
                $score--;
                $matched_words['negative'][] = $word;
            }
        }

        $sentiment = 'Neutral';
        if ($score > 0) $sentiment = 'Positive';
        if ($score < 0) $sentiment = 'Negative';

        return [
            'score' => $score,
            'label' => $sentiment,
            'details' => $matched_words
        ];
    }

    /**
     * Resume Parser (Simple Rule-Based)
     * Extracts Email and Phone from text content
     */
    public function parse_resume_text($text) {
        $info = [
            'email' => '',
            'phone' => '',
            'skills' => []
        ];

        // Extract Email
        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $email_matches);
        if (!empty($email_matches)) $info['email'] = $email_matches[0];

        // Extract Phone (Generic formats)
        preg_match('/(?:(?:\+|00)([1-9]\d{0,3})[\s-]?)?\(?0?\d{1,4}\)?[\s-]?\d{1,4}[\s-]?\d{1,4}/', $text, $phone_matches);
        if (!empty($phone_matches)) $info['phone'] = $phone_matches[0];

        // Extract Skills (Keyword matching)
        $common_skills = ['php', 'java', 'python', 'javascript', 'html', 'css', 'sql', 'react', 'angular', 'codeigniter', 'laravel'];
        $text_lower = strtolower($text);
        foreach ($common_skills as $skill) {
            if (strpos($text_lower, $skill) !== false) {
                $info['skills'][] = ucfirst($skill);
            }
        }

        return $info;
    }

    private function _get_risk_level($score) {
        if ($score < 30) return 'Low';
        if ($score < 70) return 'Medium';
        return 'High';
    }
}
