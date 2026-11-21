<?php

declare(strict_types=1);

use craft\rector\Rector\RenameMethodCallRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig
        ->ruleWithConfiguration(RenameClassConstFetchRector::class, [
            new RenameClassAndConstFetch('craft\services\Assets', 'EVENT_GET_ASSET_URL', 'craft\elements\Asset', 'EVENT_DEFINE_URL'),
        ]);

    $methodCallRenames = [
        new MethodCallRename('craft\elements\Asset', 'getUri', 'getPath'),
    ];
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, $methodCallRenames);
    $rectorConfig->ruleWithConfiguration(RenameMethodCallRector::class, $methodCallRenames);
};
