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

        // 1. Tenure Check
        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        $join_date = new DateTime($user->created_at);
        $now = new DateTime();
        $tenure_months = $join_date->diff($now)->m + ($join_date->diff($now)->y * 12);

        if ($tenure_months > 12 && $tenure_months < 24) {
            $score += 10; // "The 1-2 year itch"
            $risk_factors[] = "Mid-term tenure volatility";
        }

        // 2. Attendance Pattern (Last 30 days)
        $this->db->where('user_id', $user_id);
        $this->db->where('att_date >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->where('status', 'absent');
        $absents = $this->db->count_all_results('attendance');
        
        if ($absents > 3) {
            $score += 20;
            $risk_factors[] = "High recent absenteeism";
        }

        // 3. Sentiment Analysis on recent feedback/updates
        // (Simplified: checking recent status updates if modules exist)
        /* This would hook into a feedback module if it exists */

        // 4. Salary/Role Stagnation (Mock logic as salary history table might not exist)
        // If we had a salary_history table, we'd check last hike date.
        
        // Cap score
        $score = min($score, 100);

        return [
            'user_id' => $user_id,
            'risk_score' => $score,
            'risk_level' => $this->_get_risk_level($score),
            'factors' => $risk_factors
        ];
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
