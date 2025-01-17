<?php

declare(strict_types=1);

use Webmozart\Assert\Assert;

require __DIR__ . '/gen_callmap_utils.php';

// Load+normalize autogenerated maps
$baseMaps = [];
foreach (glob(__DIR__."/../dictionaries/autogen/CallMap_*.php") as $file) {
    Assert::eq(preg_match('/_(\d+)\.php/', $file, $matches), 1);
    $version = $matches[1];

    $baseMaps[$version] = normalizeCallMap(require $file);
    writeCallMap($file, $baseMaps[$version]);
}

ksort($baseMaps);
$last = array_key_last($baseMaps);

// Load+normalize hand-written diff maps
$customMaps = [];
foreach (glob(__DIR__."/../dictionaries/override/CallMap_*.php") as $file) {
    Assert::eq(preg_match('/_(\d+)\.php/', $file, $matches), 1);
    $version = $matches[1];

    $customMaps[$version] = normalizeCallMap(require $file);
}

// Merge hand-written full maps into autogenerated full maps, write to files
foreach ($customMaps as $version => $data) {
    writeCallMap("dictionaries/CallMap_$version.php", normalizeCallMap(array_replace($baseMaps[$version] ?? [], $data)));
}

// Cleanup hand-written full maps, removing data that is the same in the autogenerated full maps
foreach ($customMaps as $version => $data) {
    foreach ($data as $name => $func) {
        $baseRet = ($baseMaps[$version][$name][0] ?? null);
        $myRet = $func[0] ?? null;
        if ($baseRet && $myRet && $baseRet === "?{$myRet}") {
            unset($data[$name]);
        }
        if (($baseMaps[$version][$name] ?? null) === $func) {
            unset($data[$name]);
        }
    }
    $data = normalizeCallMap($data);
    writeCallMap("dictionaries/override/CallMap_{$version}.php", $data);
}