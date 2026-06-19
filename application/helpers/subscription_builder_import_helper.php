<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('subscription_builder_xlsx_col_index')) {
    function subscription_builder_xlsx_col_index($cell_ref)
    {
        if (!preg_match('/^([A-Z]+)/', (string) $cell_ref, $matches)) {
            return 0;
        }
        $letters = $matches[1];
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }
}

if (!function_exists('subscription_builder_xlsx_cell_value')) {
    function subscription_builder_xlsx_cell_value($cell)
    {
        $attrs = $cell->attributes();
        $type = isset($attrs['t']) ? (string) $attrs['t'] : '';

        if ($type === 'inlineStr') {
            if (isset($cell->is->t)) {
                return (string) $cell->is->t;
            }
            return '';
        }

        if (isset($cell->v)) {
            return (string) $cell->v;
        }

        return '';
    }
}

if (!function_exists('subscription_builder_parse_xlsx_file')) {
    /**
     * Parse first worksheet of an .xlsx file into a 2D row array.
     *
     * @param string $filepath
     * @return array<int, array<int, string>>
     */
    function subscription_builder_parse_xlsx_file($filepath)
    {
        if (!class_exists('ZipArchive') || !is_file($filepath)) {
            return array();
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return array();
        }

        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($sheet_xml === false || $sheet_xml === '') {
            return array();
        }

        $shared_strings = array();
        if ($shared_xml !== false && $shared_xml !== '') {
            $shared = @simplexml_load_string($shared_xml);
            if ($shared) {
                $shared_ns = $shared->getNamespaces(true);
                $main_ns = isset($shared_ns['']) ? $shared_ns[''] : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                foreach ($shared->children($main_ns) as $si) {
                    if (isset($si->t)) {
                        $shared_strings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            if (isset($run->t)) {
                                $text .= (string) $run->t;
                            }
                        }
                        $shared_strings[] = $text;
                    } else {
                        $shared_strings[] = '';
                    }
                }
            }
        }

        $xml = @simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array();
        }

        $ns = $xml->getNamespaces(true);
        $main_ns = isset($ns['']) ? $ns[''] : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheet_data = $xml->children($main_ns)->sheetData;
        if (!$sheet_data) {
            return array();
        }

        $grid = array();
        foreach ($sheet_data->children($main_ns) as $row) {
            $row_attrs = $row->attributes();
            $row_num = isset($row_attrs['r']) ? (int) $row_attrs['r'] : 0;
            $cells = array();

            foreach ($row->children($main_ns) as $cell) {
                $cell_attrs = $cell->attributes();
                $ref = isset($cell_attrs['r']) ? (string) $cell_attrs['r'] : '';
                $col_index = subscription_builder_xlsx_col_index($ref);
                $type = isset($cell_attrs['t']) ? (string) $cell_attrs['t'] : '';

                if ($type === 's' && isset($cell->v)) {
                    $idx = (int) $cell->v;
                    $value = isset($shared_strings[$idx]) ? $shared_strings[$idx] : '';
                } else {
                    $value = subscription_builder_xlsx_cell_value($cell);
                }

                $cells[$col_index] = trim((string) $value);
            }

            if (empty($cells)) {
                continue;
            }

            ksort($cells);
            $max = max(array_keys($cells));
            $line = array();
            for ($i = 0; $i <= $max; $i++) {
                $line[] = isset($cells[$i]) ? $cells[$i] : '';
            }

            if ($row_num > 0) {
                $grid[$row_num] = $line;
            } else {
                $grid[] = $line;
            }
        }

        if (!empty($grid) && array_keys($grid) !== range(0, count($grid) - 1)) {
            ksort($grid);
            $grid = array_values($grid);
        }

        return $grid;
    }
}
