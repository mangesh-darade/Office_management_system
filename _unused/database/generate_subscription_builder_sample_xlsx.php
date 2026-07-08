<?php
/**
 * One-off generator for database/subscription_builder_import_sample.xlsx
 * Run: php database/generate_subscription_builder_sample_xlsx.php
 */

$csv_path = __DIR__ . '/subscription_builder_import_sample.csv';
$xlsx_path = __DIR__ . '/subscription_builder_import_sample.xlsx';

if (!is_file($csv_path)) {
    fwrite(STDERR, "CSV sample not found.\n");
    exit(1);
}

$rows = array();
$handle = fopen($csv_path, 'r');
if ($handle === false) {
    fwrite(STDERR, "Unable to read CSV sample.\n");
    exit(1);
}
while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    $rows[] = $line;
}
fclose($handle);

if (empty($rows)) {
    fwrite(STDERR, "CSV sample is empty.\n");
    exit(1);
}

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ZipArchive extension is required.\n");
    exit(1);
}

$shared = array();
$shared_index = array();
$get_shared_index = function ($value) use (&$shared, &$shared_index) {
    $value = (string) $value;
    if (!array_key_exists($value, $shared_index)) {
        $shared_index[$value] = count($shared);
        $shared[] = $value;
    }
    return $shared_index[$value];
};

$sheet_rows_xml = '';
foreach ($rows as $row_num => $cols) {
    $r = $row_num + 1;
    $cells_xml = '';
    foreach ($cols as $col_num => $value) {
        $col_letter = column_letter($col_num);
        $ref = $col_letter . $r;
        $idx = $get_shared_index($value);
        $cells_xml .= '<c r="' . $ref . '" t="s"><v>' . $idx . '</v></c>';
    }
    $sheet_rows_xml .= '<row r="' . $r . '">' . $cells_xml . '</row>';
}

$shared_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">';
foreach ($shared as $text) {
    $shared_xml .= '<si><t>' . xml_escape($text) . '</t></si>';
}
$shared_xml .= '</sst>';

$sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetData>' . $sheet_rows_xml . '</sheetData>'
    . '</worksheet>';

$workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Catalog" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

$rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$workbook_rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    . '</Relationships>';

$content_types_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    . '</Types>';

if (is_file($xlsx_path)) {
    unlink($xlsx_path);
}

$zip = new ZipArchive();
if ($zip->open($xlsx_path, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Unable to create XLSX file.\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $content_types_xml);
$zip->addFromString('_rels/.rels', $rels_xml);
$zip->addFromString('xl/workbook.xml', $workbook_xml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels_xml);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet_xml);
$zip->addFromString('xl/sharedStrings.xml', $shared_xml);
$zip->close();

echo "Created: {$xlsx_path}\n";

function column_letter($index)
{
    $index = (int) $index;
    $letter = '';
    do {
        $letter = chr(65 + ($index % 26)) . $letter;
        $index = (int) floor($index / 26) - 1;
    } while ($index >= 0);
    return $letter;
}

function xml_escape($value)
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
