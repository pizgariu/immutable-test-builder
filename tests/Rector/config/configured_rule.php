<?php

declare(strict_types=1);

use Pizgariu\ImmutableTestBuilder\Rector\Class_\RemoveRedundantModifierRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RemoveRedundantModifierRector::class);
};
