#!/usr/bin/env python3
"""Extract Reports_attendance export methods into attendance_report_export_helper.php."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
content = (ROOT / 'application/controllers/Reports_attendance.php').read_text(encoding='utf-8')


def extract(name):
    pat = rf'    private function {re.escape(name)}\s*\([^)]*\)\s*\{{'
    m = re.search(pat, content)
    if not m:
        return None
    start = m.end()
    depth = 1
    i = start
    while i < len(content) and depth > 0:
        if content[i] == '{':
            depth += 1
        elif content[i] == '}':
            depth -= 1
        i += 1
    return content[start:i - 1].strip()


def transform_body(body, old_name):
    body = body.replace('$this->db', '$db')
    body = body.replace(
        '$this->build_attendance_export_summaries(',
        'attendance_report_build_export_summaries($db, ',
    )
    body = body.replace(
        '$this->generate_daily_details_data(',
        'attendance_report_generate_daily_details($db, ',
    )
    body = body.replace(
        '$this->generate_attendance_pdf_html(',
        'attendance_report_employee_summary_pdf_html(',
    )
    body = re.sub(
        r"\$this->attendance_table_has_column\('([^']+)'\)",
        r"schema_table_has_column(\$db, 'attendance', '\1')",
        body,
    )
    body = body.replace(
        'isset($this->settings) ? $this->settings : null',
        '$settings',
    )
    if old_name in ('export_attendance_employee_excel', 'export_attendance_employee_pdf',
                    'export_attendance_employee_detail_excel', 'export_attendance_employee_detail_pdf'):
        # Replace user fetch block with callback
        pass
    return body


SIGNATURES = {
    'generate_daily_details_data': 'attendance_report_generate_daily_details($db, $user_id, $from, $to, $settings = null)',
    'generate_attendance_pdf_html': 'attendance_report_employee_summary_pdf_html($users, $summary, $period, $from, $to, $month, $date)',
    'export_attendance_data': 'attendance_report_export_period($period, $data, $format)',
    'export_attendance_employee_excel': 'attendance_report_export_employee_summary_csv($db, $userIds, $period, $from, $to, $month, $date, $settings, callable $fetchUsers)',
    'export_attendance_employee_pdf': 'attendance_report_export_employee_summary_pdf($db, $userIds, $period, $from, $to, $month, $date, $settings, callable $fetchUsers)',
    'export_attendance_employee_detail_excel': 'attendance_report_export_employee_detail_excel($db, $user_id, $period, $from, $to, $month, $date, $settings, callable $fetchUserName)',
    'export_attendance_employee_detail_pdf': 'attendance_report_export_employee_detail_pdf($db, $user_id, $period, $from, $to, $month, $date, $settings, callable $fetchUserName)',
}

NAMES = {
    'generate_daily_details_data': 'attendance_report_generate_daily_details',
    'generate_attendance_pdf_html': 'attendance_report_employee_summary_pdf_html',
    'export_attendance_data': 'attendance_report_export_period',
    'export_attendance_employee_excel': 'attendance_report_export_employee_summary_csv',
    'export_attendance_employee_pdf': 'attendance_report_export_employee_summary_pdf',
    'export_attendance_employee_detail_excel': 'attendance_report_export_employee_detail_excel',
    'export_attendance_employee_detail_pdf': 'attendance_report_export_employee_detail_pdf',
}

USER_FETCH_EXCEL = """
            $users = $fetchUsers($userIds);
"""

USER_FETCH_PDF = USER_FETCH_EXCEL

USER_NAME_FETCH = """
            $userName = $fetchUserName($user_id);
"""

def patch_user_fetch(body, old_name):
    if old_name in ('export_attendance_employee_excel', 'export_attendance_employee_pdf'):
        pat = r"// Get user names\s*\n\s*\$users = \[\];\s*\n\s*if \(\$this->db->table_exists\('users'\)\) \{[\s\S]*?\}\s*\n\s*\n\s*\$summary"
        rep = USER_FETCH_EXCEL + "\n            $summary"
        body = re.sub(pat, rep, body, count=1)
    elif old_name in ('export_attendance_employee_detail_excel', 'export_attendance_employee_detail_pdf'):
        pat = r"// Get user name\s*\n\s*\$userName = 'Unknown';\s*\n\s*if \(\$this->db->table_exists\('users'\)\) \{[\s\S]*?\}\s*\n\s*\n\s*// Generate daily"
        rep = USER_NAME_FETCH + "\n            // Generate daily"
        body = re.sub(pat, rep, body, count=1)
    return body


def main():
    header = """<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance report export helpers (CSV/PDF/HTML).
 */

"""
    parts = [header]
    for old, new in NAMES.items():
        body = extract(old)
        if body is None:
            print('MISSING', old)
            continue
        body = transform_body(body, old)
        body = patch_user_fetch(body, old)
        sig = SIGNATURES[old]
        fn_name = new
        parts.append(f"if (!function_exists('{fn_name}')) {{\n    function {sig}\n    {{\n        {body}\n    }}\n}}\n\n")
        print('OK', old)

    out = ROOT / 'application/helpers/attendance_report_export_helper.php'
    out.write_text(''.join(parts), encoding='utf-8')
    print('wrote', out)


if __name__ == '__main__':
    main()
