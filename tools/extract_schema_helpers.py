#!/usr/bin/env python3
"""Extract controller ensure_schema blocks into schema helper files."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CTRL = ROOT / 'application' / 'controllers'
HELP = ROOT / 'application' / 'helpers'

EXTRACTS = [
    {
        'controller': 'Clients.php',
        'method': 'ensure_schema',
        'helper': 'clients_schema_helper.php',
        'func': 'clients_schema_ensure',
        'desc': 'CRM clients and client_contacts tables',
    },
    {
        'controller': 'Expenses.php',
        'method': 'ensure_schema',
        'helper': 'expenses_schema_helper.php',
        'func': 'expenses_schema_ensure',
        'desc': 'Expense categories and expenses tables',
    },
    {
        'controller': 'Requirements.php',
        'method': 'ensure_schema',
        'helper': 'requirements_schema_helper.php',
        'func': 'requirements_schema_ensure',
        'desc': 'Requirements module tables',
    },
    {
        'controller': 'Announcements.php',
        'method': 'ensure_schema',
        'helper': 'announcements_schema_helper.php',
        'func': 'announcements_schema_ensure',
        'desc': 'Announcements table and column upgrades',
    },
]


def extract_method_body(content, method_name):
    pat = rf'private function {re.escape(method_name)}\s*\([^)]*\)\s*\{{'
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


def transform_body(body):
    body = body.replace('$this->db', '$db')
    return body


def build_helper(desc, func, body):
    header = "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n"
    doc = f"/**\n * {desc}\n */\n\n"
    fn = f"if (!function_exists('{func}')) {{\n    function {func}($db)\n    {{\n        static $done = false;\n        if ($done) {{\n            return;\n        }}\n        $done = true;\n        {body}\n    }}\n}}\n"
    return header + doc + fn


def patch_controller(path, method_name, func):
    content = path.read_text(encoding='utf-8')
    body = extract_method_body(content, method_name)
    if body is None:
        print('WARN no method', path.name, method_name)
        return
    helper_name = func.replace('_schema_ensure', '_schema').replace('_ensure', '_schema')
    if helper_name.endswith('_schema_schema'):
        helper_name = func.rsplit('_', 2)[0] + '_schema'
    # clients_schema_ensure -> clients_schema
    helper_file = func.replace('_ensure', '').replace('schema_schema', 'schema')
    if not helper_file.endswith('_schema'):
        parts = func.split('_')
        helper_file = '_'.join(parts[:-1]) if parts[-1] == 'ensure' else func + '_helper'
    # simpler mapping
    helper_map = {
        'clients_schema_ensure': 'clients_schema',
        'expenses_schema_ensure': 'expenses_schema',
        'requirements_schema_ensure': 'requirements_schema',
        'announcements_schema_ensure': 'announcements_schema',
    }
    helper = helper_map.get(func, func)

    replacement = f"""    private function {method_name}() {{
        $this->load->helper('{helper}');
        {func}($this->db);
    }}"""

    pat = rf'    private function {re.escape(method_name)}\s*\([^)]*\)\s*\{{'
    m = re.search(pat, content)
    if not m:
        print('WARN patch failed', path.name)
        return
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
    new_content = content[:start] + replacement + content[i:]
    path.write_text(new_content, encoding='utf-8')
    print('patched', path.name)


def main():
    for item in EXTRACTS:
        ctrl_path = CTRL / item['controller']
        content = ctrl_path.read_text(encoding='utf-8')
        body = extract_method_body(content, item['method'])
        if body is None:
            continue
        # Clients/Requirements already have logic - don't double static $done in helper if body doesn't use it
        helper_body = transform_body(body)
        helper_path = HELP / item['helper']
        helper_path.write_text(build_helper(item['desc'], item['func'], helper_body), encoding='utf-8')
        print('wrote', helper_path.name)
        patch_controller(ctrl_path, item['method'], item['func'])


if __name__ == '__main__':
    main()
