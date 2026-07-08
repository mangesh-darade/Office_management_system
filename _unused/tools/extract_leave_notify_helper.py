#!/usr/bin/env python3
"""Extract Leave_requests notification methods into helper."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CTRL = ROOT / 'application' / 'controllers' / 'Leave_requests.php'
HELP = ROOT / 'application' / 'helpers' / 'leave_requests_notify_helper.php'

METHODS = [
    ('_notify_leave_applied', 'leave_requests_notify_applied'),
    ('_notify_leave_change', 'leave_requests_notify_change'),
]


def extract_method(content, name):
    pat = rf'    private function {re.escape(name)}\s*\([^)]*\)\s*\{{'
    m = re.search(pat, content)
    if not m:
        raise RuntimeError(name)
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


def transform(body):
    body = body.replace('$this->', '$CI->')
    return body


def build_helper(methods):
    header = "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n/**\n * Leave / WFH email notifications (apply + approve/reject).\n */\n\n"
    parts = [header]
    for old, new in methods:
        body = transform(methods[old])
        parts.append(
            f"if (!function_exists('{new}')) {{\n"
            f"    function {new}("
        )
        # preserve signature from original - read from file
    return None


def main():
    content = CTRL.read_text(encoding='utf-8')
    blocks = {}
    for old, new in METHODS:
        body = extract_method(content, old)
        blocks[new] = transform(body)

    helper = """<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Leave / WFH email notifications (apply + approve/reject).
 */

if (!function_exists('leave_requests_notify_applied')) {
    function leave_requests_notify_applied($leave_ids, $user_id, $type_id, $selected_lead_id, $selected_admin_id)
    {
        $CI =& get_instance();
        %s
    }
}

if (!function_exists('leave_requests_notify_change')) {
    function leave_requests_notify_change($leave_id, $status, $comments)
    {
        $CI =& get_instance();
        %s
    }
}
""" % (blocks['leave_requests_notify_applied'], blocks['leave_requests_notify_change'])

    HELP.write_text(helper, encoding='utf-8')

    for old, new in METHODS:
        content = re.sub(
            rf'\$this->{re.escape(old)}\(',
            f"{new}(",
            content,
        )

    # Remove private methods
    for old, _ in METHODS:
        pat = rf'    /\*\*[\s\S]*?\*/\s*private function {re.escape(old)}\s*\([^)]*\)\s*\{{'
        m = re.search(pat, content)
        if not m:
            pat = rf'    private function {re.escape(old)}\s*\([^)]*\)\s*\{{'
            m = re.search(pat, content)
        if not m:
            raise RuntimeError(f'cannot remove {old}')
        start = m.start()
        brace = content.find('{', m.end() - 1)
        depth = 1
        i = brace + 1
        while i < len(content) and depth > 0:
            if content[i] == '{':
                depth += 1
            elif content[i] == '}':
                depth -= 1
            i += 1
        content = content[:start] + content[i:]

    # Load helper in constructor
    if "'leave_requests_notify'" not in content:
        content = content.replace(
            "$this->load->helper(['url','form','workday','group_filter','hierarchy_filter','company','permission','schema_columns']);",
            "$this->load->helper(['url','form','workday','group_filter','hierarchy_filter','company','permission','schema_columns','leave_requests_notify']);",
        )

    CTRL.write_text(content, encoding='utf-8')
    print('done', HELP.name)


if __name__ == '__main__':
    main()
