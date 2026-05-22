<?php

/**
 * Regenerate src/DataFixtures from var/fixture-export/database-export.json
 * without booting Symfony (avoids broken fixture autoload).
 *
 * Usage: php tools/generate-fixtures.php
 */

$projectDir = dirname(__DIR__);
chdir($projectDir);

require $projectDir . '/tools/DatabaseFixtureExporter.php';

$exporter = new DatabaseFixtureExporter($projectDir);
$exporter->generate();

echo "Done.\n";
