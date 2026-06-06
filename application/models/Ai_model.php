<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('schema_columns');
    }

    /**
     * Load user row plus department from employees when available.
     *
     * @param int $user_id
     * @return object|null
     */
    private function _get_user_profile($user_id) {
        $select = ['u.id'];
        if (schema_table_has_column($this->db, 'users', 'created_at')) {
            $select[] = 'u.created_at';
        }

        $this->db->select(implode(', ', $select));
        $this->db->from('users u');
        $this->db->where('u.id', (int) $user_id);

        if ($this->db->table_exists('employees')) {
            $this->db->join('employees e', 'e.user_id = u.id', 'left');
            if (schema_table_has_column($this->db, 'employees', 'department_id')) {
                $this->db->select('e.department_id', false);
            }
            if (schema_table_has_column($this->db, 'employees', 'department')) {
                $this->db->select('e.department', false);
            }
            if (schema_table_has_column($this->db, 'employees', 'salary_ctc')) {
                $this->db->select('e.salary_ctc', false);
            }
            if (schema_table_has_column($this->db, 'employees', 'basic_salary')) {
                $this->db->select('e.basic_salary', false);
            }
        }

        return $this->db->get()->row();
    }

    /**
     * Resolve department key used for peer comparisons.
     *
     * @param object|null $user
     * @return string|int|null
     */
    private function _user_department_key($user) {
        if (!$user) {
            return null;
        }
        if (isset($user->department_id) && $user->department_id !== '' && $user->department_id !== null) {
            return (int) $user->department_id;
        }
        if (isset($user->department) && trim((string) $user->department) !== '') {
            return trim((string) $user->department);
        }

        return null;
    }

    /**
     * Sum remaining leave balance for a user (schema-aware).
     *
     * @param int $user_id
     * @return float
     */
    private function _get_total_leave_balance($user_id) {
        if (!$this->db->table_exists('leave_balances')) {
            return 0.0;
        }

        $this->db->from('leave_balances');
        $this->db->where('user_id', (int) $user_id);
        if (schema_table_has_column($this->db, 'leave_balances', 'year')) {
            $this->db->where('year', (int) date('Y'));
        }

        if (schema_table_has_column($this->db, 'leave_balances', 'closing_balance')) {
            $this->db->select_sum('closing_balance', 'total_balance');
        } elseif (schema_table_has_column($this->db, 'leave_balances', 'remaining_leaves')) {
            $this->db->select_sum('remaining_leaves', 'total_balance');
        } elseif (
            schema_table_has_column($this->db, 'leave_balances', 'opening_balance')
            && schema_table_has_column($this->db, 'leave_balances', 'accrued')
            && schema_table_has_column($this->db, 'leave_balances', 'used')
        ) {
            $this->db->select('SUM(opening_balance + accrued - used) AS total_balance', false);
        } else {
            return 0.0;
        }

        $balance_row = $this->db->get()->row();

        return ($balance_row && isset($balance_row->total_balance) && $balance_row->total_balance !== null)
            ? (float) $balance_row->total_balance
            : 0.0;
    }

    /**
     * Predictive Analytics: Calculate Attrition Risk Score (0-100)
     * Logic: Analyzes tenure, salary hikes, leave patterns, and project load.
     */
    public function predict_attrition($user_id) {
        $score = 0;
        $risk_factors = [];

        // Load User and Department Info
        $user = $this->_get_user_profile($user_id);
        if (!$user) return ['error' => 'User not found'];

        $department_key = $this->_user_department_key($user);

        // 1. Tenure Check "The 1-2 Year Itch"
        $join_date = null;
        if (!empty($user->created_at)) {
            $join_date = new DateTime($user->created_at);
        } elseif ($this->db->table_exists('employees')) {
            $emp = $this->db->select('join_date')->from('employees')->where('user_id', (int) $user_id)->get()->row();
            if ($emp && !empty($emp->join_date)) {
                $join_date = new DateTime($emp->join_date);
            }
        }
        if (!$join_date) {
            $join_date = new DateTime();
        }
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
        $dept_avg_absents = $this->_get_dept_avg_absents($department_key);
        
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
        if ($forecast['trend'] === 'Getting Later' && isset($forecast['slope']) && $forecast['slope'] > 3) {
            $score += 15;
            $risk_factors[] = "Consistently arriving later (Slope: {$forecast['slope']})";
        }

        // 4. Leave Balance Burn Rate
        $total_balance = $this->_get_total_leave_balance($user_id);
        
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

    private function _get_dept_avg_absents($dept_key) {
        if ($dept_key === null || $dept_key === '') return 2; // Default fallback

        if (!$this->db->table_exists('employees')) {
            return 2;
        }

        $this->db->from('employees e');
        $this->db->join('users u', 'u.id = e.user_id', 'inner');
        if (is_int($dept_key) && schema_table_has_column($this->db, 'employees', 'department_id')) {
            $this->db->where('e.department_id', $dept_key);
        } elseif (schema_table_has_column($this->db, 'employees', 'department')) {
            $this->db->where('e.department', (string) $dept_key);
        } else {
            return 2;
        }
        $user_count = $this->db->count_all_results();
        if ($user_count == 0) return 2;

        $dateCol = schema_table_has_column($this->db, 'attendance', 'att_date') ? 'att_date' : 'date';
        $this->db->select('attendance.user_id');
        $this->db->from('attendance');
        $this->db->join('employees e', 'e.user_id = attendance.user_id', 'inner');
        if (is_int($dept_key) && schema_table_has_column($this->db, 'employees', 'department_id')) {
            $this->db->where('e.department_id', $dept_key);
        } else {
            $this->db->where('e.department', (string) $dept_key);
        }
        $this->db->where('attendance.' . $dateCol . ' >=', date('Y-m-d', strtotime('-30 days')));
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

        // Need at least 2 data points to compute a meaningful slope
        if ($n < 2) {
            return ['trend' => 'No Data', 'predicted_late' => false];
        }

        $x_sum = array_sum($x_values);
        $y_sum = array_sum($y_values);
        
        $xx_sum = 0;
        $xy_sum = 0;
        
        for ($j = 0; $j < $n; $j++) {
            $xx_sum += ($x_values[$j] * $x_values[$j]);
            $xy_sum += ($x_values[$j] * $y_values[$j]);
        }

        $denominator = ($n * $xx_sum) - ($x_sum * $x_sum);
        if ($denominator == 0) {
            return ['trend' => 'Stable', 'slope' => 0.0, 'avg_time' => round($y_sum / $n)];
        }

        $slope = (($n * $xy_sum) - ($x_sum * $y_sum)) / $denominator;
        
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
