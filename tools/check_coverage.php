<?php
declare(strict_types=1);

if ($argc < 2) {
    echo "Usage: php tools/check_coverage.php <clover.xml>\n";
    exit(2);
}

$clover = $argv[1];
if (! file_exists($clover)) {
    echo "Coverage file not found: $clover\n";
    exit(2);
}

$min = getenv('COVERAGE_MIN') ?: '0.60';
$minFloat = (float) $min;

$xml = simplexml_load_file($clover);
if ($xml === false) {
    echo "Failed to parse clover xml: $clover\n";
    exit(2);
}

$metrics = $xml->project->metrics;
if (! $metrics) {
    echo "No metrics found in clover xml\n";
    exit(2);
}

$covered = (int) $metrics['coveredstatements'];
$total = (int) $metrics['statements'];
$pct = $total > 0 ? $covered / $total : 0;

printf("Coverage: %.2f%% (%d/%d)\n", $pct * 100, $covered, $total);

if ($pct + 0.00001 < $minFloat) {
    printf("Coverage %.2f%% is below required %.2f%%\n", $pct * 100, $minFloat * 100);
    exit(1);
}

echo "Coverage requirement satisfied.\n";
exit(0);
