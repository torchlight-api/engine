<?php

define('FIXTURES_DIR', dirname(__DIR__).'/fixtures/tests');

function parseFixture(string $filePath): array
{
    $content = file_get_contents($filePath);
    $filename = basename($filePath);
    $isSkipped = str_starts_with($filename, '_');

    $parts = explode(':::end', $content, 2);
    $beforeEnd = $parts[0] ?? '';
    $afterEnd = isset($parts[1]) ? trim($parts[1]) : '';

    $config = [];
    $request = [];
    $code = [];
    $expected = [];
    $section = 'code';

    foreach (explode("\n", $beforeEnd) as $line) {
        $trimmed = trim($line);

        if (str_starts_with($trimmed, ':::request')) {
            $json = substr($trimmed, strlen(':::request'));
            $request = json_decode($json, true) ?? [];

            continue;
        }

        if (str_starts_with($trimmed, ':::config')) {
            $json = substr($trimmed, strlen(':::config'));
            $config = json_decode($json, true) ?? [];

            continue;
        }

        if (str_starts_with($trimmed, ':::expectation')) {
            $section = 'expectation';

            continue;
        }

        if (str_starts_with($trimmed, ':::style')) {
            $section = 'style';

            continue;
        }

        if ($section === 'code') {
            $code[] = $line;
        } elseif ($section === 'expectation') {
            $expected[] = $line;
        }
    }

    $expectedHtml = trim(implode("\n", $expected));
    $actualHtml = $afterEnd;

    $status = 'new';
    if ($isSkipped) {
        $status = 'skipped';
    } elseif ($actualHtml !== '') {
        $status = ($expectedHtml === $actualHtml) ? 'unchanged' : 'changed';
    }

    return [
        'filename' => $filename,
        'filepath' => $filePath,
        'config' => $config,
        'request' => $request,
        'code' => implode("\n", $code),
        'expected' => $expectedHtml,
        'actual' => $actualHtml,
        'status' => $status,
        'isSkipped' => $isSkipped,
    ];
}

function loadAllFixtures(): array
{
    $fixtures = [];

    foreach (glob(FIXTURES_DIR.'/*.txt') as $path) {
        $fixtures[] = parseFixture($path);
    }

    usort($fixtures, fn ($a, $b) => strcmp((string) $a['filename'], (string) $b['filename']));

    return $fixtures;
}

function acceptFixture(string $filename): bool
{
    $filepath = FIXTURES_DIR.'/'.$filename;

    if (! file_exists($filepath)) {
        return false;
    }

    $fixture = parseFixture($filepath);

    if ($fixture['status'] !== 'changed') {
        return false;
    }

    $content = file_get_contents($filepath);

    $expectPos = strpos($content, ':::expectation');
    $endPos = strpos($content, ':::end');

    if ($expectPos === false || $endPos === false) {
        return false;
    }

    $beforeExpectation = substr($content, 0, $expectPos);
    $afterEnd = substr($content, $endPos + strlen(':::end'));

    $newContent = $beforeExpectation.":::expectation\n".$fixture['actual']."\n:::end".$afterEnd;

    return file_put_contents($filepath, $newContent) !== false;
}

function groupFixtures(array $fixtures): array
{
    return [
        'changed' => array_filter($fixtures, fn ($f) => $f['status'] === 'changed'),
        'unchanged' => array_filter($fixtures, fn ($f) => $f['status'] === 'unchanged'),
        'skipped' => array_filter($fixtures, fn ($f) => $f['status'] === 'skipped'),
        'new' => array_filter($fixtures, fn ($f) => $f['status'] === 'new'),
    ];
}
