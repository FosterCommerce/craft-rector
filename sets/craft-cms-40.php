<?php

declare(strict_types=1);

use craft\rector\Rector\MethodCallToPropertyFetchRector;
use craft\rector\Rector\RemoveMethodCallParamRector;
use craft\rector\Rector\RenameMethodCallRector;
use craft\rector\SignatureConfigurator;
use Rector\Config\RectorConfig;
use Rector\Removing\ValueObject\ArgumentRemover;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassConstFetch;
use Rector\Symfony\Set\TwigSetList;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/craft-cms-40/assets.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/elements.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/events.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/field-layouts.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/fields.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/plugins.php');
    $rectorConfig->import(__DIR__ . '/craft-cms-40/project-config.php');

    $methodCallRenames = [
        new MethodCallRename('craft\base\ApplicationTrait', 'getIsSystemOn', 'getIsLive'),
        new MethodCallRename('craft\base\Model', 'getError', 'getFirstError'),
        new MethodCallRename('craft\gql\directives\FormatDateTime', 'defaultTimezone', 'defaultTimeZone'),
        new MethodCallRename('craft\helpers\ArrayHelper', 'filterByValue', 'where'),
        new MethodCallRename('craft\helpers\ElementHelper', 'createSlug', 'normalizeSlug'),
        new MethodCallRename('craft\helpers\FileHelper', 'removeFile', 'unlink'),
        new MethodCallRename('craft\helpers\UrlHelper', 'getProtocolForTokenizedUrl', 'getSchemeForTokenizedUrl'),
        new MethodCallRename('craft\helpers\UrlHelper', 'urlWithProtocol', 'urlWithScheme'),
        new MethodCallRename('craft\i18n\Locale', 'getName', 'getDisplayName'),
        new MethodCallRename('craft\i18n\Locale', 'getNativeName', 'getDisplayName'),
        new MethodCallRename('craft\services\Assets', 'getCurrentUserTemporaryUploadFolder', 'getUserTemporaryUploadFolder'),
        new MethodCallRename('craft\services\Updates', 'getIsCraftDbMigrationNeeded', 'getIsCraftUpdatePending'),
        new MethodCallRename('craft\services\Updates', 'getIsPluginDbUpdateNeeded', 'getIsPluginUpdatePending'),
    ];
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, $methodCallRenames);
    $rectorConfig->ruleWithConfiguration(RenameMethodCallRector::class, $methodCallRenames);

    $rectorConfig
        ->ruleWithConfiguration(MethodCallToPropertyFetchRector::class, [
            [
                'class' => 'craft\elements\User',
                'method' => 'getFullName',
                'property' => 'fullName',
            ],
        ]);

    $rectorConfig
        ->ruleWithConfiguration(RemoveMethodCallParamRector::class, [
            new ArgumentRemover('craft\helpers\Db', 'batchInsert', 3, null),
            new ArgumentRemover('craft\helpers\Db', 'insert', 2, null),
            new ArgumentRemover('craft\db\Command', 'batchInsert', 3, null),
            new ArgumentRemover('craft\db\Command', 'insert', 2, null),
            new ArgumentRemover('craft\db\Migration', 'batchInsert', 3, null),
            new ArgumentRemover('craft\db\Migration', 'insert', 2, null),
        ]);

    $rectorConfig
        ->ruleWithConfiguration(RenameClassRector::class, [
            'craft\app\web\UrlRule' => 'craft\web\UrlRule',
        ]);

    $rectorConfig
        ->ruleWithConfiguration(RenameClassConstFetchRector::class, [
            new RenameClassConstFetch('Craft', 'Client', 'Pro'),
            new RenameClassConstFetch('Craft', 'Personal', 'Solo'),
        ]);

    // Property/method signatures
    SignatureConfigurator::configure($rectorConfig, 'craft-cms-40');

    $rectorConfig->sets([TwigSetList::COMPOSER_BASED]);
};
