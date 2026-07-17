<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Multilingual intent resolution for AI Chat.
 *
 * Approach (practical for CI3, no MuRIL/transformers):
 * 1) Roman + native lexicon bags (EN/HI/MR/BN/AR + common typos)
 * 2) Score each tool intent
 * 3) Caller may fall back to LLM classify_intent when score is weak
 */

if (!function_exists('ai_chat_intent_lexicon')) {
    /**
     * @return array<string,array{any:string[],need:string[],boost:string[]}>
     */
    function ai_chat_intent_lexicon()
    {
        return array(
            'my_leave_balance' => array(
                'any' => array(
                    // ownership / ask
                    'my', 'mine', 'me', 'i', 'mazya', 'maja', 'maza', 'majhe', 'majhya', 'maze',
                    'mere', 'mera', 'meri', 'amar', 'ami', 'انا', 'لي', 'माझा', 'माझे', 'मेरा', 'मेरी', 'मेरे', 'আমার',
                    'sang', 'saang', 'batao', 'bataiye', 'tell', 'show', 'kiti', 'kitne', 'kitna', 'kitni', 'how many', 'किती', 'कितने', 'कितनी', 'কত',
                ),
                'need' => array(
                    // must hit leave OR balance-domain
                    'leave', 'leaves', 'leaes', 'eaes', 'laves', 'vacation', 'holiday',
                    'raja', 'raza', 'chutti', 'chuttiya', 'rujha', 'रजा', 'रजे', 'छुट्टी', 'ছুটি',
                    'balance', 'balances', 'baki', 'shillak', 'remaining', 'available', 'left', 'bachi', 'bachi hui', 'बची', 'बचा',
                    'बैलेंस', 'बॅलन्स', 'बॅलेन्स', 'बाकी', 'शिल्लक', 'cl', 'sl', 'pl', 'wfh', 'casual', 'sick', 'privilege',
                ),
                'boost' => array(
                    'leave balance', 'leaves balance', 'leave balances', 'my leave', 'my leaves',
                    'available leave', 'remaining leave', 'raja kiti', 'chutti kiti', 'leave baki',
                    'छुट्टी कितनी', 'रजा किती', 'leave किती',
                ),
            ),
            'who_on_leave_today' => array(
                'any' => array(
                    'who', 'anyone', 'list', 'show', 'kon', 'कोण', 'कौन', 'কে', 'today', 'aaj', 'आज', 'আজ', 'اليوم',
                ),
                'need' => array(
                    'leave', 'leaves', 'on leave', 'raja', 'chutti', 'रजा', 'छुट्टी', 'ছুটি',
                ),
                'boost' => array(
                    'on leave today', 'who is on leave', 'aaj kon leave', 'आज कोण रजेवर',
                ),
            ),
            'my_attendance_today' => array(
                'any' => array(
                    'my', 'mine', 'me', 'i', 'mazya', 'maja', 'mere', 'mera', 'आज', 'today', 'aaj',
                ),
                'need' => array(
                    'attendance', 'attendence', 'checkin', 'check-in', 'check in', 'checkout', 'check-out',
                    'punch', 'punch in', 'hajri', 'hazri', 'उपस्थिती', 'हाजिरी', 'حضور', 'attendance today',
                ),
                'boost' => array(
                    'my attendance', 'did i check in', 'hajri aaj', 'आजची हाजिरी',
                ),
            ),
            'my_open_tasks' => array(
                'any' => array(
                    'my', 'mine', 'me', 'mazya', 'mere', 'assigned', 'pending', 'open', 'active',
                ),
                'need' => array(
                    'task', 'tasks', 'todo', 'to-do', 'काम', 'कार्य', 'কাজ', 'مهمة', 'pending tasks',
                ),
                'boost' => array(
                    'my tasks', 'my open tasks', 'pending tasks', 'mazya tasks', 'मेरे टास्क',
                ),
            ),
            'my_daily_activity_today' => array(
                'any' => array(
                    'my', 'mine', 'today', 'aaj', 'आज',
                ),
                'need' => array(
                    'daily activity', 'work log', 'daily work', 'activity log', 'आजचा काम', 'daily update',
                    'डेलि अॅक्टिव्हिटी', 'कार्यवृत्त',
                ),
                'boost' => array(
                    'my daily activity', 'today activity', 'aajchi activity',
                ),
            ),
            'spl_pending_approvals' => array(
                'any' => array(
                    'pending', 'waiting', 'approve', 'approval', 'approvals',
                ),
                'need' => array(
                    'spl', 'reward', 'rewards', 'approval queue', 'पॉइंट्स approve', 'रिवॉर्ड',
                ),
                'boost' => array(
                    'pending spl', 'spl approval', 'reward pending', 'pending approvals',
                ),
            ),
            'who_late_today' => array(
                'any' => array('who', 'today', 'aaj', 'list', 'show', 'kon'),
                'need' => array('late', 'lateness', 'उशीर', 'late today', 'who is late'),
                'boost' => array('who is late today', 'late check in', 'aaj late'),
            ),
            'my_pending_leaves' => array(
                'any' => array('my', 'mine', 'mazya', 'mere', 'pending', 'waiting'),
                'need' => array('leave', 'leaves', 'raja', 'chutti', 'leave request', 'leave requests'),
                'boost' => array('my pending leave', 'pending leave requests', 'mazya pending leave'),
            ),
            'my_spl_points' => array(
                'any' => array('my', 'mine', 'mazya', 'mere', 'how many', 'kiti', 'total'),
                'need' => array('spl points', 'my points', 'reward points', 'my spl', 'points balance', 'points'),
                'boost' => array('my spl points', 'mazya points', 'spl score', 'my points'),
            ),
        );
    }
}

if (!function_exists('ai_chat_fold_text')) {
    /**
     * Lowercase + strip punctuation + collapse spaces (keeps unicode letters).
     *
     * @param string $text
     * @return string
     */
    function ai_chat_fold_text($text)
    {
        $t = mb_strtolower(trim((string) $text), 'UTF-8');
        $t = str_replace(array(';eaes', ';leaves', ';leave'), array(' leaves', ' leaves', ' leave'), $t);
        // Keep letters, numbers, combining marks (virama etc.) so Devanagari words stay intact
        $t = preg_replace('/[^\p{L}\p{N}\p{M}\s\-]/u', ' ', $t);
        $t = preg_replace('/\s+/u', ' ', $t);
        return trim($t);
    }
}

if (!function_exists('ai_chat_score_intent')) {
    /**
     * @param string $message
     * @return array{tool:?string,score:int,scores:array}
     */
    function ai_chat_score_intent($message)
    {
        $text = ai_chat_fold_text($message);
        // Also merge legacy normalize if present
        if (function_exists('ai_chat_normalize_message')) {
            $text .= ' ' . ai_chat_normalize_message($message);
            $text = ai_chat_fold_text($text);
        }

        $lex = ai_chat_intent_lexicon();
        $scores = array();
        $best = null;
        $best_score = 0;

        foreach ($lex as $tool => $bags) {
            $score = 0;
            $need_hit = false;
            foreach ($bags['need'] as $term) {
                $term = mb_strtolower($term, 'UTF-8');
                if ($term !== '' && mb_strpos($text, $term) !== false) {
                    $need_hit = true;
                    $score += (mb_strpos($term, ' ') !== false) ? 3 : 2;
                }
            }
            if (!$need_hit) {
                $scores[$tool] = 0;
                continue;
            }
            foreach ($bags['any'] as $term) {
                $term = mb_strtolower($term, 'UTF-8');
                if ($term === '') {
                    continue;
                }
                if (mb_strpos($text, $term) !== false) {
                    // Prefer whole-token hits for short ASCII words to reduce false positives
                    if (preg_match('/^[a-z0-9\-\s]+$/i', $term) && mb_strlen($term) <= 3) {
                        if (preg_match('/(^|\s)' . preg_quote($term, '/') . '(\s|$)/u', $text)) {
                            $score += 1;
                        }
                    } else {
                        $score += 1;
                    }
                }
            }
            foreach ($bags['boost'] as $phrase) {
                $phrase = mb_strtolower($phrase, 'UTF-8');
                if ($phrase !== '' && mb_strpos($text, $phrase) !== false) {
                    $score += 4;
                }
            }
            $scores[$tool] = $score;
            if ($score > $best_score) {
                $best_score = $score;
                $best = $tool;
            }
        }

        // Minimum confidence
        if ($best_score < 3) {
            return array('tool' => null, 'score' => $best_score, 'scores' => $scores);
        }
        return array('tool' => $best, 'score' => $best_score, 'scores' => $scores);
    }
}

if (!function_exists('ai_chat_needs_localization')) {
    /**
     * True when user message is likely non-English / code-mixed.
     *
     * @param string $message
     * @return bool
     */
    function ai_chat_needs_localization($message)
    {
        $m = (string) $message;
        if (preg_match('/[\x{0900}-\x{097F}\x{0980}-\x{09FF}\x{0600}-\x{06FF}]/u', $m)) {
            return true;
        }
        $fold = ai_chat_fold_text($m);
        $markers = array(
            'mazya', 'maja', 'majhe', 'mere', 'mera', 'kitne', 'kiti', 'aahet', 'aaj', 'sang',
            'batao', 'chutti', 'raja', 'hajri', 'hazri', 'kaam', 'kon', 'amar', 'koto',
        );
        foreach ($markers as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $fold)) {
                return true;
            }
        }
        // Mostly ASCII English → skip extra LLM localize for speed
        if (preg_match('/^[a-z0-9\s\'\-\?\!\.\,]+$/i', trim($m))) {
            return false;
        }
        return true;
    }
}
