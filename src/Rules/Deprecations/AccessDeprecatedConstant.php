<?php declare(strict_types = 1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\DeprecatableReflection;
use PHPStan\Reflection\FunctionReflection;

class AccessDeprecatedConstant implements \PHPStan\Rules\Rule
{
    /** @var Broker */
    private $broker;
    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function getNodeType(): string
    {
        return Node\Expr\ConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\ConstFetch);
        $class = $scope->getClassReflection();
        if ($class !== null && $class->isDeprecated()) {
            return [];
        }
        $trait = $scope->getTraitReflection();
        if ($trait !== null && $trait->isDeprecated()) {
            return [];
        }
        $function = $scope->getFunction();
        if ($function instanceof FunctionReflection && $function->isDeprecated()->yes()) {
            return [];
        }

        // nikic/php-parser does not let us access phpdoc comments from deprecated constants, so
        // here goes a list of hardcoded core constants. List is available at
        // https://api.drupal.org/api/drupal/deprecated/8.9.x?order=object_type&sort=asc&page=5
        $deprecatedConstants = [
            'DATETIME_STORAGE_TIMEZONE' => 'Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE instead.',
            'DATETIME_DATETIME_STORAGE_FORMAT' => 'Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT instead.',
            'DATETIME_DATE_STORAGE_FORMAT' => 'Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT instead.',
            'DRUPAL_ANONYMOUS_RID' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Session\AccountInterface::ANONYMOUS_ROLE or \Drupal\user\RoleInterface::ANONYMOUS_ID instead.',
            'DRUPAL_AUTHENTICATED_RID' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Session\AccountInterface::AUTHENTICATED_ROLE or \Drupal\user\RoleInterface::AUTHENTICATED_ID instead.',
            'REQUEST_TIME' => 'Deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use \Drupal::time()->getRequestTime(); ',
            'DRUPAL_PHP_FUNCTION_PATTERN' => 'Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Extension\ExtensionDiscovery::PHP_FUNCTION_PATTERN instead.',
            'CONFIG_ACTIVE_DIRECTORY' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Drupal core no longer creates an active directory.',
            'CONFIG_SYNC_DIRECTORY' => 'Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Site\Settings::get(\'config_sync_directory\') instead.',
            'CONFIG_STAGING_DIRECTORY' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. The staging directory was renamed to sync.',
            'LOCALE_PLURAL_DELIMITER' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Component\Gettext\PoItem::DELIMITER instead.',
            'FILE_CHMOD_DIRECTORY' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystem::CHMOD_DIRECTORY.',
            'FILE_CHMOD_FILE' => 'Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystem::CHMOD_FILE.',
            'FILE_CREATE_DIRECTORY' => 'Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY.',
            'FILE_MODIFY_PERMISSIONS' => 'Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS.',
            'FILE_EXISTS_RENAME' => 'Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME.',
            'FILE_EXISTS_REPLACE' => 'Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE.',
            'FILE_EXISTS_ERROR' => 'Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\FileSystemInterface::EXISTS_ERROR.',
            'AGGREGATOR_CLEAR_NEVER' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\aggregator\FeedStorageInterface::CLEAR_NEVER instead.',
            'COMMENT_ANONYMOUS_MAYNOT_CONTACT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\comment\CommentInterface::ANONYMOUS_MAYNOT_CONTACT instead.',
            'COMMENT_ANONYMOUS_MAY_CONTACT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\comment\CommentInterface::ANONYMOUS_MAY_CONTACT instead.',
            'COMMENT_ANONYMOUS_MUST_CONTACT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\comment\CommentInterface::ANONYMOUS_MUST_CONTACT instead.',
            'IMAGE_STORAGE_NORMAL' => 'Deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.',
            'IMAGE_STORAGE_OVERRIDE' => 'Deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.',
            'IMAGE_STORAGE_DEFAULT' => 'Deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.',
            'IMAGE_STORAGE_EDITABLE' => 'Deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.',
            'IMAGE_STORAGE_MODULE' => 'Deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.',
            'MENU_MAX_MENU_NAME_LENGTH_UI' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\system\MenuStorage::MAX_ID_LENGTH instead.',
            'NODE_NOT_PUBLISHED' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::NOT_PUBLISHED instead.',
            'NODE_PUBLISHED' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::PUBLISHED instead.',
            'NODE_NOT_PROMOTED' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::NOT_PROMOTED instead.',
            'NODE_PROMOTED' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::PROMOTED instead.',
            'NODE_NOT_STICKY' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::NOT_STICKY instead.',
            'NODE_STICKY' => 'Deprecated in drupal:8.?.? and is removed from drupal:9.0.0. Use \Drupal\node\NodeInterface::STICKY instead.',
            'RESPONSIVE_IMAGE_EMPTY_IMAGE' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use Drupal\responsive_image\ResponsiveImageStyleInterface::EMPTY_IMAGE instead.',
            'RESPONSIVE_IMAGE_ORIGINAL_IMAGE' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\responsive_image\ResponsiveImageStyleInterface::ORIGINAL_IMAGE instead.',
            'DRUPAL_USER_TIMEZONE_DEFAULT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::TIMEZONE_DEFAULT instead.',
            'DRUPAL_USER_TIMEZONE_EMPTY' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::TIMEZONE_EMPTY instead.',
            'DRUPAL_USER_TIMEZONE_SELECT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::TIMEZONE_SELECT instead.',
            'TAXONOMY_HIERARCHY_DISABLED' => 'Deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use \Drupal\taxonomy\VocabularyInterface::HIERARCHY_DISABLED instead.',
            'TAXONOMY_HIERARCHY_SINGLE' => 'Deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use \Drupal\taxonomy\VocabularyInterface::HIERARCHY_SINGLE instead.',
            'TAXONOMY_HIERARCHY_MULTIPLE' => 'Deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use \Drupal\taxonomy\VocabularyInterface::HIERARCHY_MULTIPLE instead.',
            'UPDATE_NOT_SECURE' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateManagerInterface::NOT_SECURE instead.',
            'UPDATE_REVOKED' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateManagerInterface::REVOKED instead.',
            'UPDATE_NOT_SUPPORTED' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateManagerInterface::NOT_SUPPORTED instead.',
            'UPDATE_NOT_CURRENT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateManagerInterface::NOT_CURRENT instead.',
            'UPDATE_CURRENT' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateManagerInterface::CURRENT instead.',
            'UPDATE_NOT_CHECKED' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateFetcherInterface::NOT_CHECKED instead.',
            'UPDATE_UNKNOWN' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateFetcherInterface::UNKNOWN instead.',
            'UPDATE_NOT_FETCHED' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateFetcherInterface::NOT_FETCHED instead.',
            'UPDATE_FETCH_PENDING' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\update\UpdateFetcherInterface::FETCH_PENDING instead.',
            'USERNAME_MAX_LENGTH' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::USERNAME_MAX_LENGTH instead.',
            'USER_REGISTER_ADMINISTRATORS_ONLY' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::REGISTER_ADMINISTRATORS_ONLY instead.',
            'USER_REGISTER_VISITORS' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::REGISTER_VISITORS instead.',
            'USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL' => 'Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\user\UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL instead.',
        ];
        $constantName = $this->broker->resolveConstantName($node->name, $scope);
        if (isset($deprecatedConstants[$constantName])) {
            return [
                sprintf('Call to deprecated constant %s: %s', $constantName, $deprecatedConstants[$constantName])
            ];
        }
        return [];
    }
}
