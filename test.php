<?php
// test.php — small random tests to exercise basic functions
// Usage: php test.php

declare(strict_types=1);

function randString(int $len): string
{
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$max = strlen($chars) - 1;
	$s = '';
	for ($i = 0; $i < $len; $i++) {
		$s .= $chars[random_int(0, $max)];
	}
	return $s;
}

$results = [];

// Test 1: random integer in range
$rand = random_int(1, 100);
$results[] = [
	'name' => 'random_range',
	'passed' => ($rand >= 1 && $rand <= 100),
	'value' => $rand,
];

// Test 2: random string length
$s = randString(12);
$results[] = [
	'name' => 'string_length_12',
	'passed' => (strlen($s) === 12),
	'value' => $s,
];

// Test 3: sha256 hash length
$h = hash('sha256', $s);
$results[] = [
	'name' => 'hash_length_64',
	'passed' => (strlen($h) === 64),
	'value' => $h,
];

// Test 4: shuffle preserves elements
$base = range(1, 10);
$sh = $base;
shuffle($sh);
$results[] = [
	'name' => 'shuffle_preserves_elements',
	'passed' => (count(array_diff($base, $sh)) === 0 && count($base) === count($sh)),
	'value' => $sh,
];

// Test 5: deterministic math
$results[] = [
	'name' => 'sum_1_10',
	'passed' => (array_sum($base) === 55),
	'value' => array_sum($base),
];

$all = true;
foreach ($results as $r) {
	if (!$r['passed']) {
		$all = false;
		break;
	}
}

echo json_encode(['ok' => $all, 'results' => $results], JSON_PRETTY_PRINT) . PHP_EOL;
exit($all ? 0 : 1);

