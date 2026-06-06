#!/usr/bin/env python3
"""Extract Email_settings and System_settings schema blocks into helpers."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CTRL = ROOT / 'application' / 'controllers'
HELP = ROOT / 'application' / 'helpers'


def extract_method_body(content, method_name, visibility='private'):
    pat = rf'{visibility} function {re.escape(method_name)}\s*\([^)]*\)\s*\{{'
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


def replace_controller_method(content, method_name, replacement, visibility='private'):
    pat = rf'    {visibility} function {re.escape(method_name)}\s*\([^)]*\)\s*\{{'
    m = re.search(pat, content)
    if not m:
        raise RuntimeError(f'method not found: {method_name}')
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
    return content[:start] + replacement + content[i:]


def build_email_helper(ensure_body, seed_body):
    ensure_body = ensure_body.replace('$this->insert_default_settings();', 'email_settings_schema_seed_defaults($db);')
    ensure_body = ensure_body.replace('$this->db', '$db')
    seed_body = seed_body.replace('$this->db', '$db')
    header = "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n/**\n * Email settings tables and default notification rows.\n */\n\n"
    seed_fn = f"if (!function_exists('email_settings_schema_seed_defaults')) {{\n    function email_settings_schema_seed_defaults($db)\n    {{\n        {seed_body}\n    }}\n}}\n\n"
    ensure_fn = f"if (!function_exists('email_settings_schema_ensure')) {{\n    function email_settings_schema_ensure($db)\n    {{\n        static $done = false;\n        if ($done) {{\n            return;\n        }}\n        $done = true;\n        {ensure_body}\n    }}\n}}\n"
    return header + seed_fn + ensure_fn


def build_system_helper(ensure_body, settings_body, perms_body):
    ensure_body = re.sub(r'static \$done = false;\s*if \(\$done\) \{ return; \}\s*\$done = true;\s*', '', ensure_body)
    ensure_body = ensure_body.replace('$this->insert_default_settings();', 'system_settings_schema_seed_defaults($db);')
    ensure_body = ensure_body.replace('$this->insert_default_permissions();', 'system_settings_schema_seed_role_permissions($db);')
    ensure_body = ensure_body.replace('$this->db', '$db')
    settings_body = settings_body.replace('$this->db', '$db')
    perms_body = perms_body.replace('$this->db', '$db')
    header = "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n/**\n * System settings, role_permissions, user_module_access bootstrap.\n */\n\n"
    parts = []
    parts.append(f"if (!function_exists('system_settings_schema_seed_defaults')) {{\n    function system_settings_schema_seed_defaults($db)\n    {{\n        {settings_body}\n    }}\n}}\n\n")
    parts.append(f"if (!function_exists('system_settings_schema_seed_role_permissions')) {{\n    function system_settings_schema_seed_role_permissions($db)\n    {{\n        {perms_body}\n    }}\n}}\n\n")
    parts.append(f"if (!function_exists('system_settings_schema_ensure')) {{\n    function system_settings_schema_ensure($db)\n    {{\n        static $done = false;\n        if ($done) {{\n            return;\n        }}\n        $done = true;\n        {ensure_body}\n    }}\n}}\n")
    return header + ''.join(parts)


def patch_email_settings():
    path = CTRL / 'Email_settings.php'
    content = path.read_text(encoding='utf-8')
    ensure_body = extract_method_body(content, 'ensure_schema')
    seed_body = extract_method_body(content, 'insert_default_settings')
    (HELP / 'email_settings_schema_helper.php').write_text(
        build_email_helper(ensure_body, seed_body), encoding='utf-8')
    content = replace_controller_method(content, 'ensure_schema', """    private function ensure_schema() {
        $this->load->helper('email_settings_schema');
        email_settings_schema_ensure($this->db);
    }""")
    content = content.replace(
        '        $this->insert_default_settings();\n\n        $settings =',
        "        $this->load->helper('email_settings_schema');\n        email_settings_schema_seed_defaults($this->db);\n\n        $settings =")
    content = replace_controller_method(content, 'insert_default_settings', '', visibility='private')
    # remove empty method if left - actually replace with nothing might leave orphan - delete method entirely
    content = re.sub(
        r'\n    private function insert_default_settings\(\) \{\s*\}\n',
        '\n',
        content)
    path.write_text(content, encoding='utf-8')
    print('patched Email_settings.php')


def patch_system_settings():
    path = CTRL / 'System_settings.php'
    content = path.read_text(encoding='utf-8')
    ensure_body = extract_method_body(content, 'ensure_schema')
    settings_body = extract_method_body(content, 'insert_default_settings')
    perms_body = extract_method_body(content, 'insert_default_permissions')
    (HELP / 'system_settings_schema_helper.php').write_text(
        build_system_helper(ensure_body, settings_body, perms_body), encoding='utf-8')
    content = replace_controller_method(content, 'ensure_schema', """    private function ensure_schema() {
        $this->load->helper('system_settings_schema');
        system_settings_schema_ensure($this->db);
    }""")
    for name in ('insert_default_settings', 'insert_default_permissions'):
        pat = rf'    private function {name}\s*\([^)]*\)\s*\{{'
        m = re.search(pat, content)
        if m:
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
    path.write_text(content, encoding='utf-8')
    print('patched System_settings.php')


if __name__ == '__main__':
    patch_email_settings()
    patch_system_settings()
