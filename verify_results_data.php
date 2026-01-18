<?php
require_once 'includes/config.php';

$results = getElectionResults($pdo);
foreach ($results as $position) {
    echo "Position: " . $position['position'] . "\n";
    foreach ($position['candidates'] as $candidate) {
        $photo = isset($candidate['photo']) ? $candidate['photo'] : 'UNDEFINED';
        echo " - Candidate: " . $candidate['candidate'] . " | Photo: " . $photo . "\n";
    }
}
?>