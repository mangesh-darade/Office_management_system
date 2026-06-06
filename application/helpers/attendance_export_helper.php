<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance export helpers (summary CSV/PDF).
 */

if (!function_exists('attendance_export_fetch_summary')) {
    /**
     * @param CI_DB_query_builder $db
     * @param array $userIds
     * @param int $user_id
     * @param int $role_id
     * @return array
     */
    function attendance_export_fetch_summary($db, array $userIds, $user_id, $role_id)
    {
        $db->select('a.user_id, u.name as user_name, u.email, COUNT(*) as attendance_count, MAX(a.att_date) as last_attendance_date, MIN(a.att_date) as first_attendance_date');
        $db->from('attendance a');
        $db->join('users u', 'u.id = a.user_id', 'left');
        $db->where_in('a.user_id', $userIds);

        apply_role_hierarchy_filter($db, 'a.user_id', $user_id, $role_id);

        $db->group_by('a.user_id, u.name, u.email');
        $db->order_by('u.name', 'ASC');

        return $db->get()->result();
    }
}

if (!function_exists('attendance_export_send_csv')) {
    /**
     * @param array $records
     * @return void
     */
    function attendance_export_send_csv(array $records)
    {
        $filename = 'attendance_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, array(
            'Employee Name',
            'Email',
            'Total Attendance Records',
            'First Attendance Date',
            'Last Attendance Date',
        ));

        foreach ($records as $record) {
            fputcsv($output, array(
                $record->user_name ?: 'Unknown',
                $record->email ?: '',
                $record->attendance_count,
                $record->first_attendance_date ?: '',
                $record->last_attendance_date ?: '',
            ));
        }

        fclose($output);
        exit;
    }
}

if (!function_exists('attendance_export_send_pdf')) {
    /**
     * @param array $records
     * @return void
     */
    function attendance_export_send_pdf(array $records)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #667eea; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .header-info { margin-bottom: 20px; color: #666; }
    </style>
</head>
<body>
    <h2>Attendance Summary Report</h2>
    <div class="header-info">
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Total Employees:</strong> ' . count($records) . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Email</th>
                <th>Total Records</th>
                <th>First Attendance</th>
                <th>Last Attendance</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($records as $record) {
            $html .= '<tr>
                <td>' . htmlspecialchars($record->user_name ?: 'Unknown') . '</td>
                <td>' . htmlspecialchars($record->email ?: '') . '</td>
                <td>' . $record->attendance_count . '</td>
                <td>' . ($record->first_attendance_date ?: '—') . '</td>
                <td>' . ($record->last_attendance_date ?: '—') . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>
</body>
</html>';

        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename = 'attendance_export_' . date('Y-m-d_His') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        }

        $filename = 'attendance_export_' . date('Y-m-d_His') . '.html';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $html;
        exit;
    }
}
