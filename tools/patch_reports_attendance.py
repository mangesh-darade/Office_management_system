#!/usr/bin/env python3
"""Patch Reports_attendance.php to delegate to export helpers."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CTRL = ROOT / 'application/controllers/Reports_attendance.php'

REMOVE_METHODS = [
    'generate_daily_details_data',
    'generate_attendance_pdf_html',
    'export_attendance_data',
    'export_attendance_employee_excel',
    'export_attendance_employee_pdf',
    'export_attendance_employee_detail_excel',
    'export_attendance_employee_detail_pdf',
]


def remove_method(content, name):
    """Remove one private method by name; docblock must immediately precede the function."""
    func_pat = rf'private function {re.escape(name)}\s*\('
    m = re.search(func_pat, content)
    if not m:
        print('skip remove', name)
        return content
    start = m.start()
    prefix = content[:start].rstrip()
    doc_tail = re.search(r'(\s*/\*\*(?:[^*]|\*(?!/))*\*/)\s*$', prefix)
    if doc_tail:
        start = doc_tail.start(1)
    else:
        # Optional // comment lines immediately above
        comment_tail = re.search(r'((?:\s*//[^\n]*\n)+)\s*$', prefix)
        if comment_tail:
            start = comment_tail.start(1)
    brace = content.find('{', m.end() - 1)
    if brace < 0:
        print('skip remove (no brace)', name)
        return content
    depth = 1
    i = brace + 1
    while i < len(content) and depth > 0:
        if content[i] == '{':
            depth += 1
        elif content[i] == '}':
            depth -= 1
        i += 1
    return content[:start] + content[i:]


WRAPPERS = '''
    private function _fetch_export_users(array $userIds)
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }
        $nameFlags = $this->user_employee_name_flags();
        $nameExpr = $this->user_display_name_sql_expr('u', 'e', $nameFlags);
        $this->db->select("u.id, ($nameExpr) AS name", false);
        $this->db->where_in('u.id', $userIds);
        if ($nameFlags['hasEmpTable']) {
            $this->db->join('employees e', 'e.user_id = u.id', 'left');
        }
        $this->db->from('users u');
        apply_role_hierarchy_filter($this->db, 'u.id');
        return $this->db->get()->result();
    }

    private function _fetch_export_user_name($user_id)
    {
        $users = $this->_fetch_export_users(array((int) $user_id));
        if (empty($users)) {
            return 'Unknown';
        }
        $user = $users[0];
        return isset($user->name) ? $user->name : 'Unknown';
    }

    private function build_attendance_export_summaries(array $userIds, $from, $to)
    {
        return attendance_report_build_export_summaries(
            $this->db,
            $userIds,
            $from,
            $to,
            isset($this->settings) ? $this->settings : null
        );
    }

    private function export_attendance_data($period, $data, $format)
    {
        attendance_report_export_period($period, $data, $format);
    }

    private function export_attendance_employee_excel($userIds, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_summary_csv(
            $this->db, $userIds, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function (array $ids) use ($controller) {
                return $controller->_fetch_export_users($ids);
            }
        );
    }

    private function export_attendance_employee_pdf($userIds, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_summary_pdf(
            $this->db, $userIds, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function (array $ids) use ($controller) {
                return $controller->_fetch_export_users($ids);
            }
        );
    }

    private function export_attendance_employee_detail_excel($user_id, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_detail_excel(
            $this->db, $user_id, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function ($uid) use ($controller) {
                return $controller->_fetch_export_user_name($uid);
            }
        );
    }

    private function export_attendance_employee_detail_pdf($user_id, $period, $from, $to, $month, $date)
    {
        $controller = $this;
        attendance_report_export_employee_detail_pdf(
            $this->db, $user_id, $period, $from, $to, $month, $date,
            isset($this->settings) ? $this->settings : null,
            function ($uid) use ($controller) {
                return $controller->_fetch_export_user_name($uid);
            }
        );
    }
'''


def main():
    content = CTRL.read_text(encoding='utf-8')
    for name in REMOVE_METHODS:
        content = remove_method(content, name)
    # remove old build_attendance_export_summaries if still large
    content = remove_method(content, 'build_attendance_export_summaries')
    # insert wrappers before last closing brace of class
    content = content.rstrip()
    if not content.endswith('}'):
        raise SystemExit('bad file end')
    content = content[:-1] + WRAPPERS + '\n}\n'
    # patch getName in attendance_employee - optional via separate replace
    old_get = '''        $getName = function($uid) use ($labels) {
            $label = isset($labels[$uid]) ? $labels[$uid] : null;
            if ($label) {
                $empParts = [];
                if (isset($label->emp_first_name) && trim((string)$label->emp_first_name) !== '') { $empParts[] = trim((string)$label->emp_first_name); }
                if (isset($label->emp_middle_name) && trim((string)$label->emp_middle_name) !== '') { $empParts[] = trim((string)$label->emp_middle_name); }
                if (isset($label->emp_last_name) && trim((string)$label->emp_last_name) !== '') { $empParts[] = trim((string)$label->emp_last_name); }
                if (!empty($empParts)) { return trim(implode(' ', $empParts)); }
                if (isset($label->emp_full_name) && trim((string)$label->emp_full_name) !== '') { return trim((string)$label->emp_full_name); }
                if (isset($label->emp_name) && trim((string)$label->emp_name) !== '') { return trim((string)$label->emp_name); }
                if (isset($label->full_name) && trim((string)$label->full_name) !== '') { return trim((string)$label->full_name); }
                if (isset($label->name) && trim((string)$label->name) !== '') { return trim((string)$label->name); }
                return $label->email;
            }
            return $uid ? ('User #'.$uid) : 'Unknown';
        };'''
    new_get = '''        $getName = function ($uid) use ($labels) {
            return attendance_report_user_display_name(isset($labels[$uid]) ? $labels[$uid] : null, $uid);
        };'''
    content = content.replace(old_get, new_get, 1)
    CTRL.write_text(content, encoding='utf-8')
    print('patched controller', CTRL)


if __name__ == '__main__':
    main()
