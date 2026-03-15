<?php

/**
 * Fixture Review Tool for Torchlight Engine
 *
 * Usage: composer review-tests
 * Then open: http://localhost:8000
 */

require __DIR__.'/fixtures.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_GET['accept'])) {
    header('Content-Type: application/json');
    $filename = basename((string) $_GET['accept']);
    $success = acceptFixture($filename);
    echo json_encode(['success' => $success]);
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'fixtures') {
    header('Content-Type: application/json');
    echo json_encode(loadAllFixtures());
    exit;
}

if (isset($_GET['fixture'])) {
    header('Content-Type: application/json');
    $filename = basename((string) $_GET['fixture']);
    $filepath = FIXTURES_DIR.'/'.$filename;
    if (file_exists($filepath)) {
        echo json_encode(parseFixture($filepath));
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

$fixtures = loadAllFixtures();
$grouped = groupFixtures($fixtures);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixture Review - Torchlight</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Fixture Review</h1>
        <div class="stats">
            <div class="stat changed">
                <span class="dot"></span>
                <span><?= count($grouped['changed']) ?> Changed</span>
            </div>
            <div class="stat unchanged">
                <span class="dot"></span>
                <span><?= count($grouped['unchanged']) ?> Passed</span>
            </div>
            <div class="stat skipped">
                <span class="dot"></span>
                <span><?= count($grouped['skipped']) ?> Skipped</span>
            </div>
            <?php if (count($grouped['new']) > 0) { ?>
            <div class="stat new">
                <span class="dot"></span>
                <span><?= count($grouped['new']) ?> New</span>
            </div>
            <?php } ?>
        </div>
    </header>

    <main>
        <nav class="sidebar">
            <div class="tabs">
                <button class="tab changed active" data-status="changed">
                    Changed <span class="count"><?= count($grouped['changed']) ?></span>
                </button>
                <button class="tab unchanged" data-status="unchanged">
                    OK <span class="count"><?= count($grouped['unchanged']) ?></span>
                </button>
                <button class="tab skipped" data-status="skipped">
                    Skip <span class="count"><?= count($grouped['skipped']) ?></span>
                </button>
            </div>
            <div class="fixture-list" id="fixture-list"></div>
        </nav>

        <div class="content">
            <div class="toolbar" id="toolbar" style="display: none;">
                <span class="fixture-name" id="fixture-name"></span>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="rendered">Rendered</button>
                    <button class="view-btn" data-view="source">Source</button>
                </div>
                <button class="accept-btn" id="accept-btn">Accept Changes</button>
            </div>

            <div class="comparison" id="comparison">
                <div class="empty-state">
                    <span>Select a fixture to compare</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        window.fixtures = <?= json_encode($fixtures) ?>;
    </script>
    <script src="app.js"></script>
</body>
</html>
