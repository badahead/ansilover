<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
                   ->withPaths([__DIR__ . '/src',])
                   ->withPhpSets(php83: true)
                   ->withTypeCoverageLevel(0)
                   ->withCodeQualityLevel(255);
