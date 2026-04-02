<?php
// Connect manually
$pdo = new PDO('mysql:host=localhost;dbname=official_internal_portel;charset=utf8mb4', 'root', '');

$topics = $pdo->query('SELECT id, module_id, name, has_assignment FROM training_topics')->fetchAll(PDO::FETCH_ASSOC);
$assignments = $pdo->query('SELECT id, topic_id, name FROM assignments')->fetchAll(PDO::FETCH_ASSOC);

echo "Topics:\n";
print_r($topics);
echo "\nAssignments:\n";
print_r($assignments);
