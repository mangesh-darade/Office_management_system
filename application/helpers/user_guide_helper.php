<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('user_guide_all_modules')) {
    /**
     * Full guide catalog with permission metadata (not filtered by role).
     *
     * @return list<array{id:string,slug:string,file:string,title:string,icon:string,always?:bool,access_fn?:string,access_keys?:list<string>}>
     */
    function user_guide_all_modules()
    {
        return [
            [
                'id' => '01', 'slug' => '01-login-dashboard', 'file' => '01-authentication-dashboard.md',
                'title' => 'Login & Dashboard', 'icon' => 'bi-box-arrow-in-right', 'always' => true,
            ],
            [
                'id' => '02', 'slug' => '02-people', 'file' => '02-people-organization.md',
                'title' => 'People & Organization', 'icon' => 'bi-people',
                'access_keys' => [
                    'users', 'users_list', 'users_add', 'users_edit', 'users_delete',
                    'employees', 'employees_list', 'employees_add', 'employees_edit', 'employees_delete',
                    'departments', 'designations', 'shifts', 'shifts_view', 'shifts_manage',
                    'assets', 'assets_mgmt', 'assets_manage', 'clients', 'clients_list',
                    'roles', 'recruitment', 'recruitment_jobs', 'recruitment_candidates',
                ],
            ],
            [
                'id' => '03', 'slug' => '03-attendance-leave', 'file' => '03-attendance-leave.md',
                'title' => 'Attendance & Leave', 'icon' => 'bi-calendar-check',
                'access_keys' => [
                    'attendance', 'attendance_list', 'attendance_add', 'attendance_edit', 'attendance_delete',
                    'leave_requests', 'leaves', 'leaves_list', 'leaves_add', 'leaves_edit', 'leaves_delete',
                    'leave_team', 'leave_calendar', 'leave_approve',
                ],
            ],
            [
                'id' => '04', 'slug' => '04-projects-tasks', 'file' => '04-projects-tasks.md',
                'title' => 'Projects & Tasks', 'icon' => 'bi-kanban',
                'access_keys' => [
                    'projects', 'projects_list', 'projects_add', 'projects_edit', 'projects_delete',
                    'requirements', 'tasks', 'tasks_list', 'tasks_add', 'tasks_edit', 'tasks_delete',
                    'timesheets', 'releases', 'releases_add', 'releases_edit',
                    'defects', 'defects_list', 'defects_add', 'defects_edit', 'defects_delete',
                ],
            ],
            [
                'id' => '05', 'slug' => '05-work-tracking', 'file' => '05-work-tracking.md',
                'title' => 'Work Tracking', 'icon' => 'bi-clipboard2-check',
                'access_keys' => [
                    'my_works', 'my_works_list', 'my_works_add', 'my_works_edit', 'my_works_delete',
                    'daily_activity', 'daily_activity_add', 'daily_activity_list', 'daily_activity_edit',
                    'daily_activity_export', 'daily_activity_report', 'daily_activity_delete',
                ],
            ],
            [
                'id' => '06', 'slug' => '06-reports-analytics', 'file' => '06-reports-analytics.md',
                'title' => 'Reports & Analytics', 'icon' => 'bi-graph-up',
                'access_keys' => [
                    'reports', 'analytics', 'reports_overview', 'reports_requirements',
                    'reports_tasks_assignment', 'reports_projects_status', 'reports_leaves',
                    'reports_attendance', 'reports_attendance_employee', 'reports_daily_activity',
                    'daily_activity_report', 'reports_payroll', 'reports_expenses', 'reports_performance',
                ],
            ],
            [
                'id' => '07', 'slug' => '07-communication', 'file' => '07-communication.md',
                'title' => 'Communication', 'icon' => 'bi-chat-dots',
                'access_keys' => [
                    'mail', 'sendgrid', 'whatsapp', 'chats', 'announcements', 'notifications',
                ],
            ],
            [
                'id' => '08', 'slug' => '08-finance', 'file' => '08-finance.md',
                'title' => 'Finance', 'icon' => 'bi-currency-dollar',
                'access_keys' => [
                    'payroll', 'payroll_view', 'payroll_manage',
                    'expenses', 'expenses_add', 'expenses_edit', 'expenses_delete',
                    'expenses_approve', 'expenses_reimburse', 'expenses_reports', 'expenses_categories',
                ],
            ],
            [
                'id' => '09', 'slug' => '09-training-coaching', 'file' => '09-training-coaching.md',
                'title' => 'Training & Coaching', 'icon' => 'bi-mortarboard',
                'access_keys' => [
                    'training_assessment', 'training_assessment_manage', 'training_assessment_take',
                    'training_screen_ta_dashboard', 'training_screen_ta_create', 'training_screen_ta_import',
                    'training_screen_ta_report', 'training_screen_ta_submissions', 'training_screen_ta_team_progress',
                    'training_screen_ta_my_tests', 'training', 'training_lms_manage', 'training_lms_admin',
                    'training_screen_lms_office_csv', 'external_training', 'external_training_watch',
                    'external_training_list', 'external_training_add', 'external_training_edit',
                    'coaching', 'coaching_coaches', 'coaching_clients', 'coaching_sessions',
                    'coaching_goals', 'coaching_leads', 'coaching_billing', 'coaching_reports',
                    'coaching_whatsapp_crm', 'coaching_resources', 'coaching_admin', 'coaching_portal',
                ],
            ],
            [
                'id' => '10', 'slug' => '10-administration', 'file' => '10-administration.md',
                'title' => 'Administration', 'icon' => 'bi-gear',
                'access_keys' => [
                    'settings', 'system_settings', 'permissions', 'email_settings', 'db', 'db_admin',
                    'reminders', 'reminders_list', 'reminders_add', 'reminders_edit', 'reminders_delete',
                    'activity', 'statuses', 'types', 'approvals', 'lead_mapping', 'api_integrations',
                    'admin', 'holidays', 'holidays_add', 'holidays_edit', 'holidays_delete',
                    'leave_types', 'leave_types_add', 'leave_types_edit', 'leave_types_delete',
                    'superadmin',
                ],
            ],
            [
                'id' => '11', 'slug' => '11-engagement-rewards', 'file' => '11-engagement-rewards.md',
                'title' => 'Engagement & Rewards', 'icon' => 'bi-trophy',
                'access_keys' => [
                    'rewards', 'rewards_leaderboard', 'rewards_submit', 'rewards_approve',
                    'rewards_admin', 'rewards_rules', 'rewards_manual_grant',
                    'knowledge_base', 'knowledge_base_add', 'knowledge_base_edit',
                    'helpdesk', 'helpdesk_manage', 'events', 'events_add', 'events_edit',
                    'certifications', 'certifications_approve', 'customer_feedback',
                ],
            ],
            [
                'id' => '12', 'slug' => '12-office-meals', 'file' => '12-office-meals.md',
                'title' => 'Office Meals', 'icon' => 'bi-cup-hot', 'access_fn' => 'meal_has_any_access',
            ],
        ];
    }
}

if (!function_exists('user_guide_public_module')) {
    /** Strip permission metadata before passing modules to views. */
    function user_guide_public_module(array $module)
    {
        return array(
            'id'    => $module['id'],
            'slug'  => $module['slug'],
            'file'  => $module['file'],
            'title' => $module['title'],
            'icon'  => $module['icon'],
        );
    }
}

if (!function_exists('user_guide_user_can_access_module')) {
    /** True when the logged-in role may open this guide topic. */
    function user_guide_user_can_access_module(array $module)
    {
        if (!empty($module['always'])) {
            return true;
        }

        if (!empty($module['access_fn'])) {
            $fn = (string) $module['access_fn'];
            if ($fn === 'meal_has_any_access') {
                $CI =& get_instance();
                $CI->load->helper('meal');
            }
            if (function_exists($fn)) {
                return (bool) call_user_func($fn);
            }
            return false;
        }

        $keys = isset($module['access_keys']) ? (array) $module['access_keys'] : array();
        if (empty($keys)) {
            return false;
        }

        $CI =& get_instance();
        $CI->load->helper('permission');
        foreach ($keys as $key) {
            if (has_module_access($key)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('user_guide_modules')) {
    /**
     * Guide modules visible to the logged-in user (filtered by app permissions).
     *
     * @return list<array{id:string,slug:string,file:string,title:string,icon:string}>
     */
    function user_guide_modules()
    {
        $modules = array();
        foreach (user_guide_all_modules() as $module) {
            if (user_guide_user_can_access_module($module)) {
                $modules[] = user_guide_public_module($module);
            }
        }
        return $modules;
    }
}

if (!function_exists('user_guide_module_by_slug')) {
    function user_guide_module_by_slug($slug)
    {
        $slug = strtolower(trim((string) $slug));
        foreach (user_guide_modules() as $module) {
            if ($module['slug'] === $slug || $module['id'] === $slug) {
                return $module;
            }
        }
        return null;
    }
}

if (!function_exists('user_guide_docs_path')) {
    function user_guide_docs_path()
    {
        return rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'user-guide' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('user_guide_image_base_url')) {
    function user_guide_image_base_url()
    {
        return rtrim(base_url('docs/user-guide/'), '/') . '/';
    }
}

if (!function_exists('user_guide_media_url')) {
    function user_guide_media_url($relative_path)
    {
        $relative_path = ltrim(str_replace('\\', '/', (string) $relative_path), './');
        if (preg_match('/\.webm$/i', $relative_path)) {
            $voiced = preg_replace('/\.webm$/i', '-voiced.webm', $relative_path);
            $voiced_file = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'docs'
                . DIRECTORY_SEPARATOR . 'user-guide' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $voiced);
            if (is_file($voiced_file)) {
                $relative_path = $voiced;
            }
        }
        return user_guide_image_base_url() . $relative_path;
    }
}

if (!function_exists('user_guide_render_media_figure')) {
    function user_guide_render_media_figure($alt, $src)
    {
        $alt = htmlspecialchars((string) $alt, ENT_QUOTES, 'UTF-8');
        if (strpos($src, 'http') !== 0) {
            $src = user_guide_media_url($src);
        }
        $src_esc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?: $src, PATHINFO_EXTENSION));

        if (in_array($ext, ['webm', 'mp4', 'mov', 'ogg'], true)) {
            $mime = $ext === 'mp4' ? 'video/mp4' : ($ext === 'mov' ? 'video/quicktime' : 'video/webm');
            return '<figure class="guide-figure guide-video-figure my-4">'
                . '<video class="guide-video rounded border shadow-sm w-100" controls playsinline preload="metadata" poster="">'
                . '<source src="' . $src_esc . '" type="' . $mime . '">'
                . 'Your browser does not support video. <a href="' . $src_esc . '">Download the clip</a>.'
                . '</video>'
                . ($alt !== '' ? '<figcaption class="text-muted small mt-2"><i class="bi bi-play-circle me-1"></i>' . $alt
                    . (strpos($src, '-voiced.webm') !== false ? ' <span class="badge bg-info text-dark ms-1">Voice</span>' : '')
                    . '</figcaption>' : '')
                . '</figure>';
        }

        return '<figure class="guide-figure my-4">'
            . '<img src="' . $src_esc . '" alt="' . $alt . '" class="guide-screenshot img-fluid rounded border shadow-sm" loading="lazy">'
            . ($alt !== '' ? '<figcaption class="text-muted small mt-2">' . $alt . '</figcaption>' : '')
            . '</figure>';
    }
}

if (!function_exists('user_guide_markdown_to_html')) {
    function user_guide_markdown_to_html($markdown)
    {
        $CI =& get_instance();
        $CI->load->helper('url');

        $image_base = user_guide_image_base_url();
        $lines = preg_split('/\r\n|\r|\n/', (string) $markdown);
        $html = [];
        $in_ul = false;
        $in_ol = false;
        $in_table = false;

        $close_list = function () use (&$html, &$in_ul, &$in_ol) {
            if ($in_ul) {
                $html[] = '</ul>';
                $in_ul = false;
            }
            if ($in_ol) {
                $html[] = '</ol>';
                $in_ol = false;
            }
        };

        $close_table = function () use (&$html, &$in_table) {
            if ($in_table) {
                $html[] = '</tbody></table></div>';
                $in_table = false;
            }
        };

        $inline = function ($text) use ($CI) {
            static $file_map = null;
            if ($file_map === null) {
                $file_map = [];
                foreach (user_guide_modules() as $mod) {
                    $file_map[strtolower($mod['file'])] = site_url('guide/' . $mod['slug']);
                }
            }

            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
            $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
            $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) use ($CI, $file_map) {
                $label = $m[1];
                $href = $m[2];
                $basename = strtolower(basename($href));
                if (isset($file_map[$basename])) {
                    $href = $file_map[$basename];
                } elseif (stripos($href, 'USER_GUIDE.md') !== false) {
                    $href = site_url('guide');
                } elseif (strpos($href, 'http') !== 0 && strpos($href, '#') !== 0 && strpos($href, 'mailto:') !== 0) {
                    $href = site_url(ltrim($href, '/'));
                }
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
            }, $text);
            return $text;
        };

        foreach ($lines as $line) {
            $trim = trim($line);

            if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)$/', $trim, $m)) {
                $close_list();
                $close_table();
                $html[] = user_guide_render_media_figure($m[1], $m[2]);
                continue;
            }

            if ($trim === '---') {
                $close_list();
                $close_table();
                $html[] = '<hr class="my-4">';
                continue;
            }

            if (preg_match('/^#{1,6}\s+(.+)$/', $trim, $m)) {
                $close_list();
                $close_table();
                $level = min(strlen($line) - strlen(ltrim($line, '#')), 6);
                $tag = 'h' . $level;
                $html[] = '<' . $tag . ' class="guide-heading guide-' . $tag . '">' . $inline($m[1]) . '</' . $tag . '>';
                continue;
            }

            if (preg_match('/^\|(.+)\|$/', $trim) && strpos($trim, '|') !== false) {
                $cells = array_map('trim', explode('|', trim($trim, '|')));
                if (preg_match('/^:?-+:?$/', str_replace(' ', '', $cells[0] ?? ''))) {
                    continue;
                }
                $close_list();
                if (!$in_table) {
                    $html[] = '<div class="table-responsive guide-table-wrap mb-3"><table class="table table-sm table-bordered guide-table"><tbody>';
                    $in_table = true;
                }
                $html[] = '<tr>' . implode('', array_map(function ($c) use ($inline) {
                    return '<td>' . $inline($c) . '</td>';
                }, $cells)) . '</tr>';
                continue;
            }

            if ($trim === '') {
                $close_list();
                $close_table();
                continue;
            }

            $close_table();

            if (preg_match('/^-\s+(.+)$/', $trim, $m)) {
                if ($in_ol) {
                    $html[] = '</ol>';
                    $in_ol = false;
                }
                if (!$in_ul) {
                    $html[] = '<ul class="guide-list">';
                    $in_ul = true;
                }
                $html[] = '<li>' . $inline($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trim, $m)) {
                if ($in_ul) {
                    $html[] = '</ul>';
                    $in_ul = false;
                }
                if (!$in_ol) {
                    $html[] = '<ol class="guide-list">';
                    $in_ol = true;
                }
                $html[] = '<li>' . $inline($m[1]) . '</li>';
                continue;
            }

            $close_list();
            $html[] = '<p class="guide-paragraph">' . $inline($trim) . '</p>';
        }

        $close_list();
        $close_table();

        return implode("\n", $html);
    }
}

if (!function_exists('user_guide_render_module')) {
    function user_guide_render_module($file)
    {
        $path = user_guide_docs_path() . $file;
        if (!is_file($path)) {
            return null;
        }
        $markdown = file_get_contents($path);
        if ($markdown === false) {
            return null;
        }
        return user_guide_markdown_to_html($markdown);
    }
}
