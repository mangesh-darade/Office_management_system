import re
import os

ROOT = os.path.join(r'c:\wamp64\www\Office_management_system', 'application')

FILES = [
    'controllers/Users.php',
    'controllers/Auth.php',
    'controllers/Tasks.php',
    'controllers/Db.php',
    'helpers/org_schema_helper.php',
    'helpers/dashboard_helper.php',
    'helpers/hierarchy_filter_helper.php',
    'helpers/permission_helper.php',
    'helpers/coaching_helper.php',
    'libraries/Coaching_automation.php',
    'views/tasks/form.php',
    'views/roles/index.php',
    'views/requirements/view.php',
    'views/permissions/index.php',
]


def migrate(content):
    # $this->db->field_exists('col', 'table')
    content = re.sub(
        r"\$this->db->field_exists\('([^']+)',\s*'([^']+)'\)",
        r"schema_table_has_column($this->db, '\2', '\1')",
        content,
    )
    # $this->db->field_exists('col', $var)
    content = re.sub(
        r"\$this->db->field_exists\('([^']+)',\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)",
        r"schema_table_has_column($this->db, \2, '\1')",
        content,
    )
    # $this->db->field_exists($var, 'table')
    content = re.sub(
        r"\$this->db->field_exists\((\$[a-zA-Z_][a-zA-Z0-9_]*),\s*'([^']+)'\)",
        r"schema_table_has_column($this->db, '\2', \1)",
        content,
    )
    # $CI->db->field_exists('col', 'table')
    content = re.sub(
        r"\$CI->db->field_exists\('([^']+)',\s*'([^']+)'\)",
        r"schema_table_has_column($CI->db, '\2', '\1')",
        content,
    )
    # $CI->db->field_exists($var, 'table')
    content = re.sub(
        r"\$CI->db->field_exists\((\$[a-zA-Z_][a-zA-Z0-9_]*),\s*'([^']+)'\)",
        r"schema_table_has_column($CI->db, '\2', \1)",
        content,
    )
    # standalone $db->field_exists (helpers)
    content = re.sub(
        r"(?<!\$this->)(?<!\$CI->)\$db->field_exists\('([^']+)',\s*'([^']+)'\)",
        r"schema_table_has_column($db, '\2', '\1')",
        content,
    )
    content = re.sub(
        r"(?<!\$this->)(?<!\$CI->)\$db->field_exists\((\$[a-zA-Z_][a-zA-Z0-9_]*),\s*'([^']+)'\)",
        r"schema_table_has_column($db, '\2', \1)",
        content,
    )
    return content


def ensure_controller_helper(content):
    if 'schema_columns' in content:
        return content
    m = re.search(
        r"\$this->load->helper\(\[(.*?)\]\s*\);",
        content,
        re.DOTALL,
    )
    if m:
        inner = m.group(1).strip().rstrip(',')
        rep = "$this->load->helper([" + inner + ",'schema_columns']);"
        return content.replace(m.group(0), rep, 1)
    m2 = re.search(r"(\$this->load->database\(\);)", content)
    if m2:
        return content.replace(
            m2.group(1),
            m2.group(1) + "\n        $this->load->helper('schema_columns');",
            1,
        )
    return content


for rel in FILES:
    path = os.path.join(ROOT, rel.replace('/', os.sep))
    if not os.path.isfile(path):
        print('missing', rel)
        continue
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    before = content.count('field_exists')
    if rel.startswith('controllers/'):
        content = ensure_controller_helper(content)
    content = migrate(content)
    after = content.count('field_exists')
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content)
    print(rel, 'before', before, 'after', after)
