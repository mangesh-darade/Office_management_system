<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DB schema SQL parse/diff helpers
 */

if (!function_exists('db_normalize_column_def')) {
    function db_normalize_column_def($def)
    {
        $s = trim((string)$def);
        // Strip trailing comma if present
        $s = preg_replace('/,\s*$/', '', $s);
        // Replace zero-date defaults
        $s = preg_replace(
            '/\b(timestamp|datetime|date)\b\s+NOT\s+NULL\s+DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            '$1 NULL DEFAULT NULL',
            $s
        );
        $s = preg_replace(
            '/DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            'DEFAULT NULL',
            $s
        );
        return $s;
    }
}

if (!function_exists('db_build_create_sql_from_columns')) {
    function db_build_create_sql_from_columns($table, $meta)
    {
        $cols = [];
        if (isset($meta['columns']) && is_array($meta['columns'])){
            foreach ($meta['columns'] as $c => $def){
                $cols[] = db_normalize_column_def($def);
            }
        }
        // If we have an auto-increment id column but no explicit PK, add a primary key on id
        $hasId = false; $hasPk = false;
        foreach ($cols as $line){
            if (preg_match('/^`?id`?\b/i', $line)){ $hasId = true; if (stripos($line,'auto_increment') !== false){ /* ok */ } }
            if (preg_match('/^primary\s+key\b/i', $line)){ $hasPk = true; }
        }
        if ($hasId && !$hasPk){ $cols[] = 'PRIMARY KEY (`id`)'; }
        $body = implode(",\n  ", $cols);
        $sql = "CREATE TABLE `".$table."` (\n  ".$body."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        return $sql;
    }
}

if (!function_exists('db_parse_sql_schema')) {
    function db_parse_sql_schema($path)
    {
        $res = ['database'=>'', 'tables'=>[]];
        if (!is_file($path)) { return $res; }
        $fh = @fopen($path, 'r'); if (!$fh) { return $res; }
        $currentTable = '';
        $inCreate = false; $buffer = '';
        while (!feof($fh)){
            $line = fgets($fh);
            if ($line === false) { break; }
            $trim = rtrim($line, "\r\n");
            if ($res['database'] === '' && preg_match('/^\s*--\s*Database:\s*`([^`]+)`/i', $trim, $m)){
                $res['database'] = $m[1];
            }
            // Recognize phpMyAdmin section header to at least register table presence
            if (!$inCreate && preg_match('/^\s*--\s*Table structure for table\s+`([^`]+)`/i', $trim, $hm)){
                $tname = $hm[1];
                if (!isset($res['tables'][$tname])){ $res['tables'][$tname] = ['columns'=>[], 'create_sql'=>'']; }
            }
            // Recognize DROP TABLE IF EXISTS [`db`.]`table` as a hint the table exists in file
            if (!$inCreate && preg_match('/^\s*DROP\s+TABLE\s+IF\s+EXISTS\s+(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?/i', $trim, $dm)){
                $tname = $dm[1];
                if (!isset($res['tables'][$tname])){ $res['tables'][$tname] = ['columns'=>[], 'create_sql'=>'']; }
            }
            // Recognize CREATE TABLE [IF NOT EXISTS] [`db`.]`table`
            if (!$inCreate && preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?/i', $trim, $m)){
                $inCreate = true; $currentTable = $m[1]; $buffer = $line; if (!isset($res['tables'][$currentTable])){ $res['tables'][$currentTable] = ['columns'=>[], 'create_sql'=>'']; }
                continue;
            }
            if ($inCreate){
                $buffer .= $line;
                // End of CREATE TABLE when we meet a line ending with ';'
                if (strpos($trim, ';') !== false && preg_match('/\)\s*[^;]*;\s*$/', $trim)){
                    // Extract column lines between first '(' and the closing ')'
                    $inner = $buffer;
                    $res['tables'][$currentTable]['create_sql'] = $buffer;
                    $posOpen = strpos($inner, '(');
                    $posClose = strrpos($inner, ')');
                    if ($posOpen !== false && $posClose !== false && $posClose > $posOpen){
                        $inside = substr($inner, $posOpen+1, $posClose-$posOpen-1);
                        $lines = preg_split('/\r?\n/', $inside);
                        foreach ($lines as $ln){
                            $ln = trim($ln);
                            if ($ln === '' || preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY\s+|CONSTRAINT|FOREIGN\s+KEY|INDEX)\b/i',$ln)) { continue; }
                            $ln = rtrim($ln, ",; ");
                            if (preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+(.+)$/', $ln, $mm)){
                                $col = $mm[1];
                                $res['tables'][$currentTable]['columns'][strtolower($col)] = $ln;
                            }
                        }
                    }
                    $inCreate = false; $currentTable = ''; $buffer = '';
                }
                continue;
            }
            // Also parse ALTER TABLE ... ADD COLUMN ... statements present anywhere in the file
            if (!$inCreate){
                // Recognize ALTER TABLE [`db`.]`table` ADD COLUMN ...;
                if (preg_match('/^\s*ALTER\s+TABLE\s+(?:`[^`]+`\.)?`?([A-Za-z0-9_]+)`?\s+ADD\s+COLUMN\s+(.*?);\s*$/i', $trim, $am)){
                    $t = $am[1];
                    // Normalize single column add; ignore complex multiple add with commas until ';'
                    $def = trim($am[2]);
                    // If the ALTER includes multiple clauses (", ADD KEY ..."), cut at first top-level comma
                    $defClean = $def; $paren=0; $inBack=false; $len = strlen($def);
                    for ($i=0; $i<$len; $i++){
                        $ch = $def[$i];
                        if ($ch==='`'){ $inBack = !$inBack; continue; }
                        if (!$inBack){
                            if ($ch==='('){ $paren++; }
                            elseif ($ch===')' && $paren>0){ $paren--; }
                            elseif ($ch===',' && $paren===0){ $defClean = trim(substr($def,0,$i)); break; }
                        }
                    }
                    $def = $defClean;
                    // Skip if what remains is a key/index/constraint fragment
                    if (preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY\s+|CONSTRAINT|FOREIGN\s+KEY|INDEX)\b/i', $def)){
                        continue;
                    }
                    // Extract column name from definition
                    if (preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+(.+)$/', $def, $cm)){
                        $col = strtolower($cm[1]);
                        if (!isset($res['tables'][$t])){ $res['tables'][$t] = ['columns'=>[], 'create_sql'=>'']; }
                        $res['tables'][$t]['columns'][$col] = $def;
                    }
                }
            }
        }
        fclose($fh);
        return $res;
    }
}

if (!function_exists('db_normalize_create_sql')) {
    function db_normalize_create_sql($sql)
    {
        $s = (string)$sql;
        // Normalize newlines
        $s = str_replace("\r", '', $s);
        // Remove block comments /* ... */ first
        $s = preg_replace('/\/\*.*?\*\//s', '', $s);
        // Remove inline comments that appear between comma-separated column defs: ", -- comment ... ," or ", -- comment ...)"
        // Keep the comma (separator) while stripping the comment chunk until next comma or closing parenthesis
        $s = preg_replace('/,\s*--.*?(?=,|\))/s', ',', $s);
        // Remove any remaining -- comments to end of line (safe when true line breaks exist)
        $s = preg_replace('/\s--.*$/m', '', $s);
        // Collapse multiple spaces
        $s = preg_replace('/[\t ]+/', ' ', $s);
        // Remove trailing comma before closing parenthesis inside the definition (repeat a few times just in case)
        for ($i=0; $i<3; $i++){
            $s = preg_replace('/,\s*\)/', ')', $s);
        }
        // Also trim commas before ENGINE or options if malformed
        $s = preg_replace('/,\s*(\)\s*ENGINE)/i', '$1', $s);
        // Fix malformed extra parenthesis after GENERATED columns, e.g., ") STORED) NOT NULL" -> ") STORED NOT NULL"
        $s = preg_replace('/\)\s*(STORED|VIRTUAL)\)\s+NOT\s+NULL/i', ') $1 NOT NULL', $s);
        // Replace invalid zero-date defaults that break under NO_ZERO_DATE/NO_ZERO_IN_DATE
        // e.g., `timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'` -> `timestamp NULL DEFAULT NULL`
        $s = preg_replace(
            '/\b(timestamp|datetime|date)\b\s+NOT\s+NULL\s+DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            '$1 NULL DEFAULT NULL',
            $s
        );
        // If any remaining DEFAULT '0000-...' without NOT NULL, change to DEFAULT NULL
        $s = preg_replace(
            '/DEFAULT\s+\'0000-00-00(?: 00:00:00)?\'/i',
            'DEFAULT NULL',
            $s
        );
        $s = trim($s);
        if ($s !== '' && substr(rtrim($s), -1) !== ';'){ $s .= ';'; }
        return $s;
    }
}

if (!function_exists('db_compare_scan_internal')) {
    function db_compare_scan_internal($file, $target, $dbName)
    {
        // Internal helper using connection object
        $schema = db_parse_sql_schema($file);
        $tables = [];
        $tableTypeMap = [];
        $q = $target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
        foreach ($q->result() as $r){ 
            $tables[strtolower($r->TABLE_NAME)] = true; 
            $tableTypeMap[strtolower($r->TABLE_NAME)] = strtoupper((string)$r->TABLE_TYPE); 
        }
        $cols = [];
        if (!empty($tables)){
            $rs = $target->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?", [$dbName]);
            foreach ($rs->result() as $r){ $t = strtolower($r->TABLE_NAME); $c = strtolower($r->COLUMN_NAME); if (!isset($cols[$t])) $cols[$t] = []; $cols[$t][$c] = true; }
        }
        $ops = [];
        foreach ($schema['tables'] as $tName => $meta){
            $tLower = strtolower($tName);
            if (!isset($tables[$tLower])){
                $rawSql = isset($meta['create_sql']) ? $meta['create_sql'] : db_build_create_sql_from_columns($tName, $meta['columns']);
                $ops[] = ['type'=>'create_table', 'table'=>$tName, 'sql'=>db_normalize_create_sql($rawSql)];
            } else {
                if (isset($tableTypeMap[$tLower]) && $tableTypeMap[$tLower] === 'VIEW'){ continue; }
                $existingCols = isset($cols[$tLower]) ? $cols[$tLower] : [];
                foreach ($meta['columns'] as $cName => $def){
                    if (!isset($existingCols[strtolower($cName)])){
                        $defNorm = db_normalize_column_def($def);
                        $sql = 'ALTER TABLE `'.$tName.'` ADD COLUMN '.$defNorm.';';
                        $ops[] = ['type'=>'add_column', 'table'=>$tName, 'column'=>$cName, 'sql'=>$sql];
                    }
                }
            }
        }
        return ['success'=>true, 'ops'=>$ops];
    }
}

if (!function_exists('db_build_column_sql_definition')) {
    function db_build_column_sql_definition($cRow)
    {
        $defParts = array('`' . str_replace('`', '``', $cRow->COLUMN_NAME) . '`', $cRow->COLUMN_TYPE);
        if (!empty($cRow->CHARACTER_SET_NAME)) {
            $defParts[] = 'CHARACTER SET ' . $cRow->CHARACTER_SET_NAME;
        }
        if (!empty($cRow->COLLATION_NAME)) {
            $defParts[] = 'COLLATE ' . $cRow->COLLATION_NAME;
        }
        $defParts[] = (strtoupper($cRow->IS_NULLABLE) === 'YES') ? 'NULL' : 'NOT NULL';
        if (!is_null($cRow->COLUMN_DEFAULT)) {
            $def = trim((string) $cRow->COLUMN_DEFAULT);
            $defUpper = strtoupper($def);
            if ($defUpper === 'CURRENT_TIMESTAMP' || $defUpper === 'CURRENT_TIMESTAMP()') {
                $defParts[] = 'DEFAULT CURRENT_TIMESTAMP';
            } elseif (is_numeric($def)) {
                $defParts[] = 'DEFAULT ' . $def;
            } elseif ($defUpper === 'NULL') {
                $defParts[] = 'DEFAULT NULL';
            } else {
                $defParts[] = "DEFAULT '" . str_replace("'", "''", $def) . "'";
            }
        }
        $extra = trim((string) $cRow->EXTRA);
        if ($extra !== '') {
            $defParts[] = $extra;
        }
        return db_normalize_column_def(implode(' ', array_filter($defParts)));
    }
}

if (!function_exists('db_format_create_table_sql')) {
    function db_format_create_table_sql($sqlCreate, $for_apply = false)
    {
        $sql = trim((string) $sqlCreate);
        $sql = rtrim($sql, ";\r\n ");
        if ($for_apply && stripos($sql, 'CREATE TABLE IF NOT EXISTS') === false) {
            $sql = preg_replace('/^CREATE\s+TABLE/i', 'CREATE TABLE IF NOT EXISTS', $sql, 1);
        }
        return $sql . ';';
    }
}

if (!function_exists('db_sanitize_executable_sql')) {
    function db_sanitize_executable_sql($sql)
    {
        $sql = trim((string) $sql);
        if ($sql === '') {
            return '';
        }
        $sql = preg_replace('/--[^\r\n]*/', '', $sql);
        $sql = trim($sql);
        if ($sql === '') {
            return '';
        }
        if (substr($sql, -1) !== ';') {
            $sql .= ';';
        }
        return $sql;
    }
}

if (!function_exists('db_diff_ops_to_sql')) {
    function db_diff_ops_to_sql($ops, $for_apply = false)
    {
        $lines = array();
        foreach ($ops as $op) {
            if (!is_array($op)) {
                continue;
            }
            if (!$for_apply && !empty($op['comment'])) {
                $lines[] = '-- ' . $op['comment'];
            }
            if ($for_apply && !empty($op['apply_sql'])) {
                $lines[] = $op['apply_sql'];
            } elseif (!empty($op['sql'])) {
                $lines[] = $op['sql'];
            }
        }
        return implode("\n\n", $lines);
    }
}

if (!function_exists('db_execute_diff_ops')) {
    function db_execute_diff_ops($conn, $ops)
    {
        $priority = array(
            'create_table' => 1,
            'add_column' => 2,
            'modify_column' => 3,
            'add_index' => 4,
        );
        usort($ops, function ($a, $b) use ($priority) {
            $ta = (is_array($a) && isset($a['type']) && isset($priority[$a['type']])) ? $priority[$a['type']] : 99;
            $tb = (is_array($b) && isset($b['type']) && isset($priority[$b['type']])) ? $priority[$b['type']] : 99;
            if ($ta === $tb) {
                return 0;
            }
            return ($ta < $tb) ? -1 : 1;
        });

        $conn->trans_begin();
        $applied = 0;
        $executed = array();
        $errors = array();

        foreach ($ops as $op) {
            if (!is_array($op)) {
                continue;
            }
            $raw = !empty($op['apply_sql']) ? $op['apply_sql'] : (isset($op['sql']) ? $op['sql'] : '');
            $sql = db_sanitize_executable_sql($raw);
            if ($sql === '') {
                continue;
            }
            if (!$conn->query($sql)) {
                $err = $conn->error();
                $msg = is_array($err) && !empty($err['message']) ? $err['message'] : 'Query failed';
                $errors[] = $msg . ' | ' . $sql;
                $conn->trans_rollback();
                throw new Exception($msg);
            }
            $applied++;
            $executed[] = $sql;
        }

        if ($conn->trans_status() === false) {
            $conn->trans_rollback();
            throw new Exception('Database transaction failed.');
        }

        $conn->trans_commit();
        return array(
            'applied' => $applied,
            'executed' => $executed,
            'errors' => $errors,
        );
    }
}

if (!function_exists('db_build_structure_diff')) {
    function db_build_structure_diff($src, $tgt, $read_conn)
    {
        $ops = array();
        $missing_tables = array();
        $missing_columns = array();
        $changed_columns = array();
        $missing_indexes = array();

        foreach ($src['tables'] as $tLower => $tRow) {
            $tName = $tRow->TABLE_NAME;
            $safeTable = str_replace('`', '``', $tName);

            if (!isset($tgt['tables'][$tLower])) {
                $res = $read_conn->query('SHOW CREATE TABLE `' . $safeTable . '`');
                if ($res && $res->num_rows() > 0) {
                    $row = $res->row_array();
                    $sqlCreate = isset($row['Create Table']) ? $row['Create Table'] : (isset(array_values($row)[1]) ? array_values($row)[1] : '');
                    if ($sqlCreate) {
                        $missing_tables[] = $tName;
                        $ops[] = array(
                            'type' => 'create_table',
                            'table' => $tName,
                            'comment' => 'Create table `' . $tName . '`',
                            'sql' => db_format_create_table_sql($sqlCreate, false),
                            'apply_sql' => db_format_create_table_sql($sqlCreate, true),
                        );
                    }
                }
            } else {
                $sCols = isset($src['cols'][$tLower]) ? $src['cols'][$tLower] : array();
                $tCols = isset($tgt['cols'][$tLower]) ? $tgt['cols'][$tLower] : array();

                foreach ($sCols as $cLower => $cRow) {
                    $cName = $cRow->COLUMN_NAME;
                    $fullDef = db_build_column_sql_definition($cRow);

                    if (!isset($tCols[$cLower])) {
                        $missing_columns[] = array('table' => $tName, 'column' => $cName);
                        $ops[] = array(
                            'type' => 'add_column',
                            'table' => $tName,
                            'column' => $cName,
                            'comment' => 'Add column `' . $tName . '`.`' . $cName . '`',
                            'sql' => 'ALTER TABLE `' . $safeTable . '` ADD COLUMN ' . $fullDef . ';',
                            'apply_sql' => 'ALTER TABLE `' . $safeTable . '` ADD COLUMN ' . $fullDef . ';',
                        );
                    } else {
                        $tcRow = $tCols[$cLower];
                        $diff = false;
                        if (strtolower($cRow->COLUMN_TYPE) !== strtolower($tcRow->COLUMN_TYPE)) {
                            $diff = true;
                        }
                        if (strtoupper($cRow->IS_NULLABLE) !== strtoupper($tcRow->IS_NULLABLE)) {
                            $diff = true;
                        }
                        if ($diff) {
                            $changed_columns[] = array(
                                'table' => $tName,
                                'column' => $cName,
                                'old' => $tcRow->COLUMN_TYPE,
                                'new' => $cRow->COLUMN_TYPE,
                            );
                            $ops[] = array(
                                'type' => 'modify_column',
                                'table' => $tName,
                                'column' => $cName,
                                'comment' => 'Modify column `' . $tName . '`.`' . $cName . '` (was ' . $tcRow->COLUMN_TYPE . ')',
                                'sql' => 'ALTER TABLE `' . $safeTable . '` MODIFY COLUMN ' . $fullDef . ';',
                                'apply_sql' => 'ALTER TABLE `' . $safeTable . '` MODIFY COLUMN ' . $fullDef . ';',
                            );
                        }
                    }
                }

                $sIdx = isset($src['indexes'][$tLower]) ? $src['indexes'][$tLower] : array();
                $tIdx = isset($tgt['indexes'][$tLower]) ? $tgt['indexes'][$tLower] : array();

                foreach ($sIdx as $igname => $igdata) {
                    if ($igname === 'PRIMARY') {
                        continue;
                    }
                    if (!isset($tIdx[$igname])) {
                        $cols = array_map(function ($c) {
                            return '`' . str_replace('`', '``', $c) . '`';
                        }, $igdata['columns']);
                        $type = ($igdata['type'] === 'FULLTEXT') ? 'FULLTEXT' : (($igdata['non_unique'] == 0) ? 'UNIQUE' : 'INDEX');
                        $safeIdx = str_replace('`', '``', $igname);
                        $indexSql = 'ALTER TABLE `' . $safeTable . '` ADD ' . $type . ' `' . $safeIdx . '` (' . implode(',', $cols) . ');';
                        $missing_indexes[] = array('table' => $tName, 'index' => $igname);
                        $ops[] = array(
                            'type' => 'add_index',
                            'table' => $tName,
                            'index' => $igname,
                            'comment' => 'Add index `' . $igname . '` on `' . $tName . '`',
                            'sql' => $indexSql,
                            'apply_sql' => $indexSql,
                        );
                    }
                }
            }
        }

        return array(
            'ops' => $ops,
            'missing_tables' => $missing_tables,
            'missing_columns' => $missing_columns,
            'changed_columns' => $changed_columns,
            'missing_indexes' => $missing_indexes,
            'sql' => db_diff_ops_to_sql($ops, false),
            'apply_sql' => db_diff_ops_to_sql($ops, true),
        );
    }
}

if (!function_exists('db_schema_module_tables_safe')) {
    function db_schema_module_tables_safe()
    {
        $CI->load->helper('schema_automation');
        return oms_schema_module_table_names();
    }
}

