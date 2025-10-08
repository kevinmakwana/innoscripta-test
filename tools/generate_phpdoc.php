<?php
// Lightweight PHPDoc scanner -> simple HTML docs generator
// Usage: php tools/generate_phpdoc.php

function scan_dir($dir)
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
            $files[] = $f->getPathname();
        }
    }
    return $files;
}

function parse_file($path)
{
    $code = file_get_contents($path);
    $tokens = token_get_all($code);

    $items = [];
    $i = 0;
    $lastDoc = null;
    $namespace = '';

    while (isset($tokens[$i])) {
        $t = $tokens[$i];
        if (is_array($t)) {
            if ($t[0] === T_NAMESPACE) {
                $i++;
                $ns = '';
                while (isset($tokens[$i]) && is_array($tokens[$i]) && ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NS_SEPARATOR)) {
                    $ns .= $tokens[$i][1];
                    $i++;
                }
                $namespace = $ns;
                continue;
            }

            if ($t[0] === T_DOC_COMMENT) {
                $lastDoc = $t[1];
            }

            if ($t[0] === T_CLASS || $t[0] === T_INTERFACE || $t[0] === T_TRAIT) {
                // find class name
                $j = $i + 1;
                while (isset($tokens[$j]) && $tokens[$j][0] !== T_STRING) { $j++; }
                $className = $tokens[$j][1] ?? 'UNKNOWN';
                $full = $namespace ? ($namespace . '\\' . $className) : $className;
                $items[] = ['type' => 'class', 'name' => $full, 'doc' => $lastDoc, 'methods' => []];
                $lastDoc = null;
            }

            if ($t[0] === T_FUNCTION) {
                // find function name
                $j = $i + 1;
                while (isset($tokens[$j]) && $tokens[$j][0] !== T_STRING) { $j++; }
                $fname = $tokens[$j][1] ?? 'anonymous';
                // attach to last class if present
                if (!empty($items) && $items[count($items)-1]['type'] === 'class') {
                    $items[count($items)-1]['methods'][] = ['name' => $fname, 'doc' => $lastDoc];
                } else {
                    $items[] = ['type' => 'function', 'name' => $fname, 'doc' => $lastDoc];
                }
                $lastDoc = null;
            }
        }
        $i++;
    }

    return $items;
}

$base = __DIR__ . '/..';
$appDir = $base . '/app';
$outDir = $base . '/docs/phpdoc';
if (!is_dir($outDir)) { mkdir($outDir, 0777, true); }

$files = scan_dir($appDir);
$docs = [];
foreach ($files as $f) {
    $parsed = parse_file($f);
    if ($parsed) {
        $docs[$f] = $parsed;
    }
}

$html = "<!doctype html><html><head><meta charset=\"utf-8\"><title>PHPDoc - Innoscripta Test</title><style>body{font-family:Arial,Helvetica,sans-serif}pre{background:#f7f7f7;padding:8px;border-radius:4px}</style></head><body>";
$html .= "<h1>PHPDoc Summary</h1>";
foreach ($docs as $file => $items) {
    $html .= "<h2>File: " . htmlspecialchars(str_replace($base . '/', '', $file)) . "</h2>\n";
    foreach ($items as $it) {
        if ($it['type'] === 'class') {
            $html .= "<h3>Class: " . htmlspecialchars($it['name']) . "</h3>\n";
            if ($it['doc']) { $html .= "<pre>" . htmlspecialchars(trim($it['doc'])) . "</pre>\n"; }
            if (!empty($it['methods'])) {
                $html .= "<h4>Methods</h4>\n<ul>";
                foreach ($it['methods'] as $m) {
                    $html .= "<li><b>" . htmlspecialchars($m['name']) . "</b>";
                    if ($m['doc']) { $html .= "<pre>" . htmlspecialchars(trim($m['doc'])) . "</pre>"; }
                    $html .= "</li>\n";
                }
                $html .= "</ul>\n";
            }
        } else {
            $html .= "<h3>Function: " . htmlspecialchars($it['name']) . "</h3>\n";
            if ($it['doc']) { $html .= "<pre>" . htmlspecialchars(trim($it['doc'])) . "</pre>\n"; }
        }
    }
}

$html .= "</body></html>";
file_put_contents($outDir . '/index.html', $html);
echo "Generated docs at docs/phpdoc/index.html\n";
