#!/usr/bin/env python3
"""Extract Db.php private methods into helpers and patch the controller."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DB_PHP = ROOT / 'application' / 'controllers' / 'Db.php'

CONNECTION_METHODS = {
    'is_local_db_host': 'db_is_local_db_host',
    'connect_custom': 'db_connect_custom',
    'connect_to': 'db_connect_to',
    'connect_to_verified': 'db_connect_to_verified',
    'resolve_client_connection': 'db_resolve_client_connection',
    'compare_connection_options_from_request': 'db_compare_connection_options_from_request',
    'resolve_connection': 'db_resolve_connection',
    'resolve_compare_connection': 'db_resolve_compare_connection',
}

ADMIN_METHODS = {
    'ensure_client_db_fields': 'db_ensure_client_db_fields',
    'verify_csrf': 'db_verify_csrf',
    'ensure_dm_manager_table': 'db_ensure_dm_manager_table',
    'ensure_client_migrations_table': 'db_ensure_client_migrations_table',
    'log_client_migration': 'db_log_client_migration',
    'escape_ident': 'db_escape_ident',
}

SCHEMA_METHODS = {
    'normalize_column_def': 'db_normalize_column_def',
    'build_create_sql_from_columns': 'db_build_create_sql_from_columns',
    'parse_sql_schema': 'db_parse_sql_schema',
    'normalize_create_sql': 'db_normalize_create_sql',
    'compare_scan_internal': 'db_compare_scan_internal',
    'build_column_sql_definition': 'db_build_column_sql_definition',
    'format_create_table_sql': 'db_format_create_table_sql',
    'sanitize_executable_sql': 'db_sanitize_executable_sql',
    'diff_ops_to_sql': 'db_diff_ops_to_sql',
    'execute_diff_ops': 'db_execute_diff_ops',
    'build_structure_diff': 'db_build_structure_diff',
    '_schema_module_tables_safe': 'db_schema_module_tables_safe',
}

ALL_EXTRACT = {**CONNECTION_METHODS, **ADMIN_METHODS, **SCHEMA_METHODS}

REPLACEMENTS = [
    (r'\$this->is_local_db_host\(', 'db_is_local_db_host('),
    (r'\$this->connect_custom\(', 'db_connect_custom($this, $this->db, '),
    (r'\$this->connect_to\(', 'db_connect_to($this, $this->db, '),
    (r'\$this->connect_to_verified\(', 'db_connect_to_verified($this, $this->db, '),
    (r'\$this->resolve_client_connection\(', 'db_resolve_client_connection($this, $this->client_model, $this->db, '),
    (r'\$this->compare_connection_options_from_request\(\)', 'db_compare_connection_options_from_request($this->input)'),
    (r'\$this->resolve_connection\(', 'db_resolve_connection($this, $this->client_model, $this->db, '),
    (r'\$this->resolve_compare_connection\(', 'db_resolve_compare_connection($this, $this->client_model, $this->db, '),
    (r'\$this->ensure_client_db_fields\(\)', 'db_ensure_client_db_fields($this->db)'),
    (r'\$this->verify_csrf\(\)', 'db_verify_csrf($this->session, $this->input)'),
    (r'\$this->ensure_dm_manager_table\(\)', "db_ensure_dm_manager_table($this->db, $this->dm_table)"),
    (r'\$this->ensure_client_migrations_table\(\)', "db_ensure_client_migrations_table($this->db, $this->client_migrations_table)"),
    (r'\$this->log_client_migration\(', 'db_log_client_migration($this->db, $this->session, $this->client_migrations_table, '),
    (r'\$this->escape_ident\(', 'db_escape_ident('),
    (r'\$this->normalize_column_def\(', 'db_normalize_column_def('),
    (r'\$this->build_create_sql_from_columns\(', 'db_build_create_sql_from_columns('),
    (r'\$this->parse_sql_schema\(', 'db_parse_sql_schema('),
    (r'\$this->normalize_create_sql\(', 'db_normalize_create_sql('),
    (r'\$this->compare_scan_internal\(', 'db_compare_scan_internal('),
    (r'\$this->build_column_sql_definition\(', 'db_build_column_sql_definition('),
    (r'\$this->format_create_table_sql\(', 'db_format_create_table_sql('),
    (r'\$this->sanitize_executable_sql\(', 'db_sanitize_executable_sql('),
    (r'\$this->diff_ops_to_sql\(', 'db_diff_ops_to_sql('),
    (r'\$this->execute_diff_ops\(', 'db_execute_diff_ops('),
    (r'\$this->build_structure_diff\(', 'db_build_structure_diff('),
    (r'\$this->_schema_module_tables_safe\(\)', 'db_schema_module_tables_safe()'),
]

HEADER = "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n"


def extract_method_body(content, method_name):
    """Extract body of private function method_name from PHP class."""
    patterns = [
        rf'private function {re.escape(method_name)}\s*\([^)]*\)\s*\{{',
        rf'private function {re.escape(method_name)}\s*\([^)]*\)\s*\n\s*\{{',
    ]
    start = -1
    for pat in patterns:
        m = re.search(pat, content)
        if m:
            start = m.end()
            break
    if start < 0:
        return None

    depth = 1
    i = start
    while i < len(content) and depth > 0:
        if content[i] == '{':
            depth += 1
        elif content[i] == '}':
            depth -= 1
        i += 1
    body = content[start:i - 1]
    return body.strip()


def transform_body(method_name, body, new_name):
    """Transform extracted method body for helper function."""
    body = body.replace('$this->dm_table', "$dm_table")
    body = body.replace('$this->client_migrations_table', "$client_migrations_table")

    subs = [
        (r'\$this->is_local_db_host\(', 'db_is_local_db_host('),
        (r'\$this->connect_custom\(', 'db_connect_custom($CI, $master_db, '),
        (r'\$this->connect_to\(', 'db_connect_to($CI, $master_db, '),
        (r'\$this->connect_to_verified\(', 'db_connect_to_verified($CI, $master_db, '),
        (r'\$this->resolve_client_connection\(', 'db_resolve_client_connection($CI, $client_model, $master_db, '),
        (r'\$this->normalize_column_def\(', 'db_normalize_column_def('),
        (r'\$this->build_create_sql_from_columns\(', 'db_build_create_sql_from_columns('),
        (r'\$this->parse_sql_schema\(', 'db_parse_sql_schema('),
        (r'\$this->normalize_create_sql\(', 'db_normalize_create_sql('),
        (r'\$this->build_create_sql_from_columns\(', 'db_build_create_sql_from_columns('),
        (r'\$this->format_create_table_sql\(', 'db_format_create_table_sql('),
        (r'\$this->build_column_sql_definition\(', 'db_build_column_sql_definition('),
        (r'\$this->diff_ops_to_sql\(', 'db_diff_ops_to_sql('),
        (r'\$this->sanitize_executable_sql\(', 'db_sanitize_executable_sql('),
        (r'\$this->load->helper\(', '$CI->load->helper('),
        (r'\$this->db\b', '$master_db'),
        (r'\$this->client_model\b', '$client_model'),
        (r'\$this->input\b', '$input'),
        (r'\$this->session\b', '$session'),
        (r'\$this->load->database\(', '$CI->load->database('),
    ]
    for old, new in subs:
        body = re.sub(old, new, body)

    return body


def signature_for(method_name, new_name):
    sigs = {
        'is_local_db_host': 'function db_is_local_db_host($host)',
        'connect_custom': 'function db_connect_custom($CI, $master_db, $hostname, $username, $password, $database, $port = null)',
        'connect_to': 'function db_connect_to($CI, $master_db, $database, $db_debug = false)',
        'connect_to_verified': 'function db_connect_to_verified($CI, $master_db, $database, $context = \'\')',
        'resolve_client_connection': 'function db_resolve_client_connection($CI, $client_model, $master_db, $client_id, $options = array())',
        'compare_connection_options_from_request': 'function db_compare_connection_options_from_request($input)',
        'resolve_connection': 'function db_resolve_connection($CI, $client_model, $master_db, $client_id, $manual_config = array())',
        'resolve_compare_connection': 'function db_resolve_compare_connection($CI, $client_model, $master_db, $client_id, $db_name, $is_master = false, $conn_options = array())',
        'ensure_client_db_fields': 'function db_ensure_client_db_fields($db)',
        'verify_csrf': 'function db_verify_csrf($session, $input)',
        'ensure_dm_manager_table': 'function db_ensure_dm_manager_table($db, $dm_table = \'dm_manager\')',
        'ensure_client_migrations_table': 'function db_ensure_client_migrations_table($db, $client_migrations_table = \'client_migrations\')',
        'log_client_migration': 'function db_log_client_migration($db, $session, $client_migrations_table, $client_id, $client_name, $dbName, $action, $tables, $columns, $file, $details = null)',
        'escape_ident': 'function db_escape_ident($name)',
        'normalize_column_def': 'function db_normalize_column_def($def)',
        'build_create_sql_from_columns': 'function db_build_create_sql_from_columns($table, $meta)',
        'parse_sql_schema': 'function db_parse_sql_schema($path)',
        'normalize_create_sql': 'function db_normalize_create_sql($sql)',
        'compare_scan_internal': 'function db_compare_scan_internal($file, $target, $dbName)',
        'build_column_sql_definition': 'function db_build_column_sql_definition($cRow)',
        'format_create_table_sql': 'function db_format_create_table_sql($sqlCreate, $for_apply = false)',
        'sanitize_executable_sql': 'function db_sanitize_executable_sql($sql)',
        'diff_ops_to_sql': 'function db_diff_ops_to_sql($ops, $for_apply = false)',
        'execute_diff_ops': 'function db_execute_diff_ops($conn, $ops)',
        'build_structure_diff': 'function db_build_structure_diff($src, $tgt, $read_conn)',
        '_schema_module_tables_safe': 'function db_schema_module_tables_safe()',
    }
    return sigs.get(method_name, f'function {new_name}()')


def build_helper_file(title, methods, content):
    parts = [HEADER, f"/**\n * {title}\n */\n\n"]
    for method_name, new_name in methods.items():
        body = extract_method_body(content, method_name)
        if body is None:
            print(f'WARN: could not extract {method_name}')
            continue
        body = transform_body(method_name, body, new_name)
        sig = signature_for(method_name, new_name)
        parts.append(f"if (!function_exists('{new_name}')) {{\n    {sig}\n    {{\n        {body}\n    }}\n}}\n\n")
    return ''.join(parts)


def remove_private_methods(content, method_names):
    for method_name in method_names:
        patterns = [
            rf'    private function {re.escape(method_name)}\s*\([^)]*\)\s*\{{',
            rf'    /\*\*[\s\S]*?\*/\s*\n    private function {re.escape(method_name)}\s*\([^)]*\)\s*\{{',
        ]
        for pat in patterns:
            m = re.search(pat, content)
            if not m:
                continue
            start = m.start()
            brace_start = content.find('{', m.end() - 1)
            depth = 1
            i = brace_start + 1
            while i < len(content) and depth > 0:
                if content[i] == '{':
                    depth += 1
                elif content[i] == '}':
                    depth -= 1
                i += 1
            content = content[:start] + content[i:]
            break
    return content


def patch_constructor(content):
    old = "$this->load->helper(['url','form','permission','schema_columns']);"
    new = "$this->load->helper(['url','form','permission','schema_columns','db_connection','db_admin','db_schema_sql']);"
    return content.replace(old, new)


def patch_get_csrf_token(content):
    old = """    public function get_csrf_token(){
        if (!$this->session->userdata('db_csrf_token')) {
            $this->session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        echo json_encode(['token' => $this->session->userdata('db_csrf_token')]);
    }"""
    new = """    public function get_csrf_token(){
        echo json_encode(['token' => db_csrf_token($this->session)]);
    }"""
    return content.replace(old, new)


def main():
    content = DB_PHP.read_text(encoding='utf-8')
    original = content

    conn_helper = build_helper_file('DB connection helpers for admin Db controller', CONNECTION_METHODS, content)
    admin_helper = build_helper_file('DB admin helpers (CSRF, migration log, schema bootstrap tables)', ADMIN_METHODS, content)
    schema_helper = build_helper_file('DB schema SQL parse/diff helpers', SCHEMA_METHODS, content)

    # Add db_csrf_token to admin helper
    admin_helper += """
if (!function_exists('db_csrf_token')) {
    function db_csrf_token($session)
    {
        if (!$session->userdata('db_csrf_token')) {
            $session->set_userdata('db_csrf_token', bin2hex(openssl_random_pseudo_bytes(16)));
        }
        return $session->userdata('db_csrf_token');
    }
}
"""

    (ROOT / 'application' / 'helpers' / 'db_connection_helper.php').write_text(conn_helper, encoding='utf-8')
    (ROOT / 'application' / 'helpers' / 'db_admin_helper.php').write_text(admin_helper, encoding='utf-8')
    (ROOT / 'application' / 'helpers' / 'db_schema_sql_helper.php').write_text(schema_helper, encoding='utf-8')

    for old, new in REPLACEMENTS:
        content = re.sub(old, new, content)

    content = remove_private_methods(content, list(ALL_EXTRACT.keys()))
    content = patch_constructor(content)
    content = patch_get_csrf_token(content)

    # index() CSRF token generation
    content = content.replace(
        "$csrf_token = $this->session->userdata('db_csrf_token');\n        if (!$csrf_token){\n             $csrf_token = bin2hex(openssl_random_pseudo_bytes(16));\n             $this->session->set_userdata('db_csrf_token', $csrf_token);\n        }",
        "$csrf_token = db_csrf_token($this->session);"
    )

    DB_PHP.write_text(content, encoding='utf-8')
    print(f'Db.php: {len(original.splitlines())} -> {len(content.splitlines())} lines')
    print('Helpers written.')


if __name__ == '__main__':
    main()
