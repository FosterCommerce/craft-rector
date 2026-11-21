<?php

declare(strict_types=1);

use craft\rector\Rector\RenameMethodCallRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function(RectorConfig $rectorConfig): void {
    $methodCallRenames = [
        new MethodCallRename('craft\base\Element', 'getHasFreshContent', 'getIsFresh'),
        new MethodCallRename('craft\base\Element', 'getIsUnsavedDraft', 'getIsUnpublishedDraft'),
    ];
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, $methodCallRenames);
    $rectorConfig->ruleWithConfiguration(RenameMethodCallRector::class, $methodCallRenames);
};
