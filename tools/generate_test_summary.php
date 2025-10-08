<?php

// Usage: php tools/generate_test_summary.php /path/to/junit.xml /path/to/output.md
if ($argc < 3) {
    fwrite(STDERR, "Usage: php generate_test_summary.php <junit-xml> <output-md>\n");
    exit(2);
}

$in = $argv[1];
$out = $argv[2];

if (! file_exists($in)) {
    fwrite(STDERR, "JUnit XML not found: $in\n");
    exit(3);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($in);
if ($xml === false) {
    fwrite(STDERR, "Failed to parse JUnit XML: $in\n");
    foreach (libxml_get_errors() as $err) {
        fwrite(STDERR, $err->message);
    }
    exit(4);
}

// Fetch top-level totals when present
$totals = [
    'tests' => null,
    'assertions' => null,
    'failures' => null,
    'errors' => null,
    'skipped' => null,
    'time' => null,
];
if (isset($xml->testsuite)) {
    $root = $xml->testsuite[0];
    foreach ($totals as $k => $_) {
        $attr = (string) $root[$k];
        if ($attr !== '') {
            $totals[$k] = $attr;
        }
    }
}

// Collect all testcases
$cases = [];
foreach ($xml->xpath('//testcase') as $tc) {
    $attrs = $tc->attributes();
    $name = (string) ($attrs['name'] ?? '');
    $file = (string) ($attrs['file'] ?? '');
    $time = (float) ($attrs['time'] ?? 0.0);
    $assertions = isset($attrs['assertions']) ? (int) $attrs['assertions'] : null;
    $classname = (string) ($attrs['classname'] ?? '');

    // normalize classname to nice form
    if ($classname !== '') {
        $group = str_replace('.', '\\', $classname);
    } elseif ($file !== '') {
        $group = $file;
    } else {
        $group = $name;
    }

    $cases[] = [
        'name' => $name,
        'file' => $file,
        'time' => $time,
        'assertions' => $assertions,
        'classname' => $classname,
        'group' => $group,
    ];
}

// Per-group aggregation (group ~= test class)
$groups = [];
foreach ($cases as $c) {
    $g = $c['group'];
    if (! isset($groups[$g])) {
        $groups[$g] = ['tests' => 0, 'assertions' => 0, 'time' => 0.0];
    }
    $groups[$g]['tests'] += 1;
    if ($c['assertions'] !== null) {
        $groups[$g]['assertions'] += $c['assertions'];
    }
    $groups[$g]['time'] += $c['time'];
}

// Sort groups by time desc for highlights
uasort($groups, function ($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Top N slowest test cases
usort($cases, function ($a, $b) {
    return $b['time'] <=> $a['time'];
});
$top = array_slice($cases, 0, 10);

$md = [];
$md[] = '# Test Summary';
$md[] = '';
$md[] = 'Generated: '.date('Y-m-d H:i:s');
$md[] = '';
$md[] = '## Overall';
$md[] = '';
if ($totals['tests'] !== null) {
    $md[] = '- Total tests: '.$totals['tests'];
    $md[] = '- Total assertions: '.$totals['assertions'];
    $md[] = '- Failures: '.$totals['failures'];
    $md[] = '- Errors: '.$totals['errors'];
    $md[] = '- Skipped: '.$totals['skipped'];
    $md[] = '- Total time: '.$totals['time'].'s';
} else {
    $md[] = '- Total tests: '.count($cases);
    $md[] = '- Total assertions: '.array_sum(array_map(function ($g) {
        return $g['assertions'];
    }, $groups));
    $md[] = '- Failures: 0';
    $md[] = '- Errors: 0';
    $md[] = '- Skipped: 0';
    $md[] = '- Total time: '.array_sum(array_map(function ($c) {
        return $c['time'];
    }, $cases)).'s';
}

$md[] = '';
$md[] = '## Top-level suites';
$md[] = '';
// Heuristic: list suites named 'Unit' and 'Feature' if present
$suiteNames = [];
foreach ($xml->xpath('//testsuite') as $ts) {
    $name = (string) $ts['name'];
    if (in_array($name, ['Unit', 'Feature']) && ! in_array($name, $suiteNames)) {
        $suiteNames[] = $name;
    }
}
foreach ($suiteNames as $sn) {
    $ts = $xml->xpath("//testsuite[@name='$sn']");
    if ($ts && isset($ts[0])) {
        $attr = $ts[0]->attributes();
        $md[] = '- '.$sn.': '.((string) $attr['tests']).' tests, '.((string) $attr['assertions']).' assertions, time '.((string) $attr['time']).'s';
    }
}
if (empty($suiteNames)) {
    $md[] = '- (no top-level Unit/Feature suites detected)';
}

$md[] = '';
$md[] = '## Per-class highlights (top time contributors)';
$md[] = '';
$count = 0;
foreach ($groups as $gname => $gdata) {
    $count++;
    $md[] = '- '.$gname.' — '.$gdata['tests'].' tests, '.$gdata['assertions'].' assertions, time '.round($gdata['time'], 6).'s';
    if ($count >= 20) {
        break;
    } // limit output
}

$md[] = '';
$md[] = '## Top 10 slowest test cases';
$md[] = '';
$md[] = '| Rank | Test case | File | Time (s) |';
$md[] = '|------|-----------|-----:|---------:|';
$rank = 1;
foreach ($top as $t) {
    // sanitize fields to avoid breaking Markdown tables
    $tcName = (string) $t['name'];
    $tcName = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $tcName);
    $file = (string) ($t['file'] ?: '-');
    $file = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $file);
    $time = sprintf('%.6f', $t['time']);
    $md[] = '| '.$rank.' | '.$tcName.' | '.$file.' | '.$time.' |';
    $rank++;
}

$md[] = '';
$md[] = '> Note: times are taken from the junit XML supplied to this script.';
$md[] = '';
$md[] = '## Notes & next actions';
$md[] = '';
$md[] = '- Consider focusing optimization on the slowest classes/tests above (DB indexes, query improvements, or test isolation).';
$md[] = '- This file is generated automatically during CI.';

$content = implode("\n", $md)."\n";
if (@file_put_contents($out, $content) === false) {
    fwrite(STDERR, "Failed to write output file: $out\n");
    exit(5);
}

echo "Wrote test summary to $out\n";
exit(0);
