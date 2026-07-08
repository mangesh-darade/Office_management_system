import re
import os

ROOT = os.path.join(r'c:\wamp64\www\Office_management_system', 'application', 'controllers')

REPORTS_BASE = ['Reports_hr.php', 'Reports_projects.php']

OTHER = [
    'Payroll.php',
    'Roles.php',
    'Dashboard.php',
    'Departments.php',
    'Daily_activity.php',
    'Leave_requests.php',
    'Profile.php',
    'Projects.php',
    'Clients.php',
    'Settings.php',
    'Permissions.php',
    'Reminders.php',
    'Requirements.php',
    'My_works.php',
]


def replace_reports(content):
    return re.sub(
        r"\$this->db->field_exists\('([^']+)',\s*'([^']+)'\)",
        r"$this->schema_has_column('\2', '\1')",
        content,
    )


def replace_schema_table(content):
    return re.sub(
        r"\$this->db->field_exists\('([^']+)',\s*'([^']+)'\)",
        r"schema_table_has_column($this->db, '\2', '\1')",
        content,
    )


def ensure_helper(content):
    if 'schema_columns' in content:
        return content

    m = re.search(
        r"\$this->load->helper\(\[(.*?)\]\s*\);",
        content,
        re.DOTALL,
    )
    if m and 'schema_columns' not in m.group(1):
        inner = m.group(1).strip()
        if inner.endswith(','):
            inner = inner[:-1]
        replacement = "$this->load->helper([" + inner + ",'schema_columns']);"
        return content.replace(m.group(0), replacement, 1)

    m2 = re.search(r"(\$this->load->database\(\);)", content)
    if m2:
        return content.replace(
            m2.group(1),
            m2.group(1) + "\n        $this->load->helper('schema_columns');",
            1,
        )

    return content


for name in REPORTS_BASE:
    path = os.path.join(ROOT, name)
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    content = replace_reports(content)
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content)
    print('reports', name)

for name in OTHER:
    path = os.path.join(ROOT, name)
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    if not re.search(r"->field_exists\(", content):
        print('skip', name)
        continue
    content = ensure_helper(content)
    content = replace_schema_table(content)
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content)
    print('other', name)
