<?php
$mysqli = new mysqli('localhost', 'root', '', 'admin_stadmin_internal_portal');
$sql = "SELECT q.id, q.status AS q_status, q.source_module, t.status AS t_status, t.id AS tx_id
FROM reward_approval_queue q
LEFT JOIN reward_transactions t ON t.id = q.transaction_id
WHERE q.source_module = 'spl'
ORDER BY q.id DESC";
$r = $mysqli->query($sql);
echo "SPL QUEUE vs TX STATUS:\n";
while ($row = $r->fetch_assoc()) {
    if ($row['q_status'] !== $row['t_status']) {
        echo 'MISMATCH: ' . json_encode($row) . "\n";
    } else {
        echo 'ok: ' . json_encode($row) . "\n";
    }
}
