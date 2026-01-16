<?php

namespace wcf\system\wsdb\exporter;

use wcf\data\object\type\ObjectTypeCache;
use wcf\data\package\PackageCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\exporter\AbstractExporter;
use wcf\system\importer\ImportHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

final class LinkDatabaseExporter extends AbstractExporter
{
    /**
     * @inheritDoc
     */
    protected $methods = [
        'dev.hanashi.wsdb.links' => 'Database',
        'dev.hanashi.wsdb.links.permission' => 'Permissions',
        'dev.hanashi.wsdb.links.category' => 'Categories',
        'dev.hanashi.wsdb.links.category.acl' => 'CategoryACLs',
        'dev.hanashi.wsdb.links.option' => 'Options',
        'dev.hanashi.wsdb.links.links' => 'Links',
        'dev.hanashi.wsdb.links.option.values' => 'OptionValues',
        'dev.hanashi.wsdb.links.comment' => 'Comments',
        'dev.hanashi.wsdb.links.comment.response' => 'CommentResponses',
    ];

    #[\Override]
    public function init()
    {
        $this->database = WCF::getDB();
        $this->fileSystemPath = WCF_DIR;
    }

    #[\Override]
    public function getDefaultDatabasePrefix()
    {
        return 'wcf' . WCF_N . '_';
    }

    #[\Override]
    public function validateFileAccess()
    {
        // no file access needable
        return true;
    }

    #[\Override]
    public function validateSelectedData(array $selectedData)
    {
        $package = PackageCache::getInstance()->getPackageByIdentifier('de.pehbeh.links');
        if ($package === null) {
            throw new UserInputException('selectedData', 'linkPackageNotInstalled');
        }

        return parent::validateSelectedData($selectedData);
    }

    #[\Override]
    public function getQueue()
    {
        $queue = [];

        if (\in_array('dev.hanashi.wsdb.links', $this->selectedData)) {
            $queue[] = 'dev.hanashi.wsdb.links';
            if (\in_array('dev.hanashi.wsdb.links.permission', $this->selectedData)) {
                $queue[] = 'dev.hanashi.wsdb.links.permission';
            }

            if (\in_array('dev.hanashi.wsdb.links.category', $this->selectedData)) {
                $queue[] = 'dev.hanashi.wsdb.links.category';
                if (\in_array('dev.hanashi.wsdb.links.category.acl', $this->selectedData)) {
                    $queue[] = 'dev.hanashi.wsdb.links.category.acl';
                }
            }
            if (\in_array('dev.hanashi.wsdb.links.option', $this->selectedData)) {
                $queue[] = 'dev.hanashi.wsdb.links.option';
            }
            if (\in_array('dev.hanashi.wsdb.links.links', $this->selectedData)) {
                $queue[] = 'dev.hanashi.wsdb.links.links';
                if (
                    \in_array('dev.hanashi.wsdb.links.option', $this->selectedData)
                    && \in_array('dev.hanashi.wsdb.links.option.values', $this->selectedData)
                ) {
                    $queue[] = 'dev.hanashi.wsdb.links.option.values';
                }
                if (\in_array('dev.hanashi.wsdb.links.comment', $this->selectedData)) {
                    $queue[] = 'dev.hanashi.wsdb.links.comment';
                }
                if (\in_array('dev.hanashi.wsdb.links.comment.response', $this->selectedData)) {
                    $queue[] = 'dev.hanashi.wsdb.links.comment.response';
                }
            }
        }

        return $queue;
    }

    #[\Override]
    public function getSupportedData(): array
    {
        return [
            'dev.hanashi.wsdb.links' => [
                'dev.hanashi.wsdb.links.permission',
                'dev.hanashi.wsdb.links.category',
                'dev.hanashi.wsdb.links.category.acl',
                'dev.hanashi.wsdb.links.option',
                'dev.hanashi.wsdb.links.links',
                'dev.hanashi.wsdb.links.option.values',
                'dev.hanashi.wsdb.links.comment',
                'dev.hanashi.wsdb.links.comment.response',
            ],
        ];
    }

    public function countDatabase(): int
    {
        return 1;
    }

    public function exportDatabase(): void
    {
        ImportHandler::getInstance()
            ->getImporter('dev.hanashi.wsdb.links')
            ->import(1, []);
    }

    public function countPermissions(): int
    {
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_user_group";

        $statement = $this->database->prepare($sql);
        $statement->execute();

        return $statement->fetchSingleColumn();
    }

    public function exportPermissions(int $offset, int $limit): void
    {
        $sql = "SELECT      *
                FROM        wcf1_user_group
                ORDER BY    groupID";

        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute();

        $groupIDs = [];
        while ($row = $statement->fetchArray()) {
            ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.user.group', $row['groupID'], $row['groupID']);
            $groupIDs[] = $row['groupID'];
        }
        if ($groupIDs === []) {
            return;
        }

        $permissionMap = [
            // users
            'user.links.canViewLinkEntry' => ['canViewRecord'],
            'user.links.canAddLinkEntry' => ['canAddRecord'],
            'user.links.canAddLinkEntryWithoutModeration' => ['canAddRecordWithoutModeration'],
            'user.links.canUploadAttachment' => ['canUploadAttachment'],
            'user.links.canDownloadAttachment' => ['canDownloadAttachment'],
            'user.links.canViewAttachmentPreview' => ['canViewAttachmentPreview'],
            'user.links.canEditLinkEntry' => ['canEditOwnRecord'],
            'user.links.canDeleteLinkEntry' => ['canDeleteOwnRecord'],
            'user.links.canAddComment' => [
                'canAddComment',
                'canAddReview',
            ],
            'user.links.canAddCommentWithoutModeration' => ['canAddCommentWithoutModeration'],
            'user.links.canEditComment' => [
                'canEditOwnComment',
                'canEditOwnReview',
            ],
            'user.links.canDeleteComment' => [
                'canDeleteOwnComment',
                'canDeleteOwnReview',
            ],
            // mod
            'mod.links.canModerateLinkEntry' => [
                'canEditRecord',
                'canViewDeletedRecord',
                'canRestoreRecord',
                'canDeleteRecordCompletely',
                'canEnableRecord',
            ],
            'mod.links.canDeleteLinkEntry' => ['canDeleteRecord'],
        ];

        $permissions = \array_keys($permissionMap);
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('option_value.groupID IN (?)', [$groupIDs]);
        $conditionBuilder->add('group_option.optionName IN (?)', [$permissions]);

        $sql = "SELECT      group_option.optionName,
                            option_value.groupID,
                            option_value.optionValue
                FROM        wcf1_user_group_option group_option
                INNER JOIN  wcf1_user_group_option_value option_value
                        ON  option_value.optionID = group_option.optionID
                " . $conditionBuilder;
        $statement = $this->database->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        while ($row = $statement->fetchArray()) {
            $acls = $permissionMap[$row['optionName']] ?? [];
            foreach ($acls as $acl) {
                ImportHandler::getInstance()
                    ->getImporter('dev.hanashi.wsdb.links.permission')
                    ->import(
                        0,
                        [
                            'objectID' => 1,
                            'optionValue' => $row['optionValue'],
                            'groupID' => $row['groupID'],
                        ],
                        ['optionName' => $acl]
                    );
            }
        }
    }

    public function countCategories(): int
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.category',
            'de.pehbeh.links.category'
        );
        if ($objectType === null) {
            return 0;
        }

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_category
                WHERE   objectTypeID = ?";

        $statement = $this->database->prepare($sql);
        $statement->execute([$objectType->objectTypeID]);

        return $statement->fetchSingleColumn();
    }

    public function exportCategories(int $offset, int $limit): void
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.category',
            'de.pehbeh.links.category'
        );
        if ($objectType === null) {
            return;
        }

        $sql = "SELECT      *
                FROM        wcf1_category
                WHERE       objectTypeID = ?
                ORDER BY    parentCategoryID, categoryID";
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute([$objectType->objectTypeID]);
        $categories = $i18nValues = [];
        while ($row = $statement->fetchArray()) {
            $row['description'] = $row['description'] ?? '';

            $categories[$row['categoryID']] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'parentCategoryID' => $row['parentCategoryID'],
                'showOrder' => $row['showOrder'],
                'time' => $row['time'],
                'isDisabled' => $row['isDisabled'],
            ];

            if (\str_starts_with($row['title'], 'wcf.category')) {
                $i18nValues[] = $row['title'];
            }
            if (\str_starts_with($row['description'], 'wcf.category')) {
                $i18nValues[] = $row['description'];
            }
            if ($row['additionalData'] !== null && @\unserialize($row['additionalData']) !== false) {
                $oldAdditionalData = \unserialize($row['additionalData']);
                $newAdditionalData = [];
                if (isset($oldAdditionalData['linkEntryCategoryIcon'])) {
                    $iconData = \explode(';', $oldAdditionalData['linkEntryCategoryIcon']);
                    if (\count($iconData) === 2) {
                        $newAdditionalData['icon'] = $oldAdditionalData['linkEntryCategoryIcon'];
                    } elseif (!empty(\trim($iconData[0]))) {
                        $newAdditionalData['icon'] = $iconData[0] . ';' . 'false';
                    }
                }
                $categories[$row['categoryID']]['additionalData'] = \serialize($newAdditionalData);
            }
        }

        $i18nValues = $this->getI18nValues($i18nValues);

        foreach ($categories as $categoryID => $categoryData) {
            $i18nData = [];
            if (isset($i18nValues[$categoryData['title']])) {
                $i18nData['title'] = $i18nValues[$categoryData['title']];
            }
            if (isset($i18nValues[$categoryData['description']])) {
                $i18nData['description'] = $i18nValues[$categoryData['description']];
            }

            ImportHandler::getInstance()
                ->getImporter('dev.hanashi.wsdb.links.category')
                ->import(
                    $categoryID,
                    $categoryData,
                    ['i18n' => $i18nData]
                );
        }
    }

    public function countCategoryACLs(): int
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.acl',
            'de.pehbeh.links.category'
        );
        if ($objectType === null) {
            return 0;
        }

        $sql = "SELECT (
                    SELECT      COUNT(*)
                    FROM        wcf1_acl_option_to_group acl_option_to_group
                    INNER JOIN  wcf1_acl_option acl_option
                            ON  acl_option.optionID = acl_option_to_group.optionID
                    WHERE       acl_option.objectTypeID = ?
                ) + (
                    SELECT      COUNT(*)
                    FROM        wcf1_acl_option_to_user acl_option_to_user
                    INNER JOIN  wcf1_acl_option acl_option
                            ON  acl_option.optionID = acl_option_to_user.optionID
                    WHERE       acl_option.objectTypeID = ?
                ) AS count";
        $statement = $this->database->prepare($sql);
        $statement->execute([$objectType->objectTypeID, $objectType->objectTypeID]);

        return $statement->fetchSingleColumn();
    }

    public function exportCategoryACLs(int $offset, int $limit): void
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.acl',
            'de.pehbeh.links.category'
        );
        if ($objectType === null) {
            return;
        }

        $aclMap = [
            'canViewCategory' => [
                'canViewCategory',
                'canViewRecord',
            ],
            'canAddLinkEntry' => [
                'canAddRecord',
            ],
        ];

        $sql = "(
                    SELECT  acl_option.optionName, acl_option.optionID,
                            option_to_group.objectID, option_to_group.optionValue, 0 AS userID, option_to_group.groupID
                    FROM    wcf1_acl_option_to_group option_to_group,
                            wcf1_acl_option acl_option
                    WHERE   acl_option.optionID = option_to_group.optionID
                            AND acl_option.objectTypeID = ?
                )
                UNION
                (
                    SELECT  acl_option.optionName, acl_option.optionID,
                            option_to_user.objectID, option_to_user.optionValue, option_to_user.userID, 0 AS groupID
                    FROM    wcf1_acl_option_to_user option_to_user,
                            wcf1_acl_option acl_option
                    WHERE   acl_option.optionID = option_to_user.optionID
                            AND acl_option.objectTypeID = ?
                )
                ORDER BY    optionID, objectID, userID, groupID";
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute([$objectType->objectTypeID, $objectType->objectTypeID]);
        while ($row = $statement->fetchArray()) {
            $acls = $aclMap[$row['optionName']] ?? [];
            foreach ($acls as $acl) {
                $data = [
                    'objectID' => $row['objectID'],
                    'optionValue' => $row['optionValue'],
                ];
                if ($row['userID']) {
                    $data['userID'] = $row['userID'];
                }
                if ($row['groupID']) {
                    $data['groupID'] = $row['groupID'];
                }

                ImportHandler::getInstance()
                    ->getImporter('dev.hanashi.wsdb.links.category.acl')
                    ->import(
                        0,
                        $data,
                        ['optionName' => $acl]
                    );
            }
        }
    }

    public function countOptions(): int
    {
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_links_option";

        $statement = $this->database->prepare($sql);
        $statement->execute();

        return $statement->fetchSingleColumn();
    }

    public function exportOptions(int $offset, int $limit): void
    {
        $sql = "SELECT  optionID,
                        categoryID
                FROM    wcf1_links_option_to_category";
        $statement = $this->database->prepare($sql);
        $statement->execute();
        $categories = $statement->fetchMap('optionID', 'categoryID', false);

        $sql = "SELECT      *
                FROM        wcf1_links_option
                ORDER BY    optionID";
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute();

        while ($row = $statement->fetchArray()) {
            ImportHandler::getInstance()
                ->getImporter('dev.hanashi.wsdb.links.option')
                ->import(
                    $row['optionID'],
                    $row,
                    [
                        'categories' => $categories[$row['optionID']] ?? [],
                    ]
                );
        }
    }

    public function countLinks(): int
    {
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_links";

        $statement = $this->database->prepare($sql);
        $statement->execute();

        return $statement->fetchSingleColumn();
    }

    public function exportLinks(int $offset, int $limit): void
    {
        $sql = "SELECT      *
                FROM        wcf1_links
                ORDER BY    linkID";
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute();

        $tags = $this->getLinkTags();

        while ($row = $statement->fetchArray()) {
            ImportHandler::getInstance()
                ->getImporter('dev.hanashi.wsdb.links.links')
                ->import(
                    $row['linkID'],
                    $row,
                    [
                        'tags' => $tags[$row['linkID']] ?? [],
                    ]
                );
        }
    }

    public function countOptionValues(): int
    {
        return $this->countLinks();
    }

    public function exportOptionValues(int $offset, int $limit): void
    {
        $sql = "SELECT      linkID
                FROM        wcf1_links
                ORDER BY    linkID";
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute();
        $linkIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
        if ($linkIDs === []) {
            return;
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('links_option_value.linkID IN (?)', [$linkIDs]);

        $sql = "SELECT      links_option_value.*,
                            links_option.optionType
                FROM        wcf1_links_option_value links_option_value
                INNER JOIN  wcf1_links_option links_option
                        ON  links_option.optionID = links_option_value.optionID
                " . $conditionBuilder . "
                ORDER BY    links_option_value.linkID";
        $statement = $this->database->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        $optionValues = [];
        while ($row = $statement->fetchArray()) {
            $optionValues[$row['linkID']][] = $row;
        }
        if ($optionValues === []) {
            return;
        }

        foreach ($optionValues as $linkID => $data) {
            ImportHandler::getInstance()
                ->getImporter('dev.hanashi.wsdb.links.option.values')
                ->import(
                    0,
                    $data,
                    [
                        'linkID' => $linkID,
                    ]
                );
        }
    }

    /**
     * @return array<int, string[]>
     */
    private function getLinkTags(): array
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.tagging.taggableObject',
            'de.pehbeh.links.linkEntry'
        );
        if ($objectType === null) {
            return [];
        }

        $sql = "SELECT      tag_to_object.objectID,
                            tag.name
                FROM        wcf1_tag tag
                INNER JOIN  wcf1_tag_to_object tag_to_object
                        ON  tag_to_object.tagID = tag.tagID
                WHERE       tag_to_object.objectTypeID";
        $statement = $this->database->prepare($sql);
        $statement->execute();

        return $statement->fetchMap('objectID', 'name', false);
    }

    /**
     * @param string[] $i18nValues
     * @return string[][][]
     */
    private function getI18nValues(array $i18nValues): array
    {
        if (empty($i18nValues)) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("language_item.languageItem IN (?)", [$i18nValues]);

        $sql = "SELECT      language_item.languageItem, language_item.languageItemValue, language.languageCode
                FROM        wcf1_language_item language_item
                LEFT JOIN   wcf1_language language
                ON          language_item.languageID = language.languageID
                " . $conditions;
        $statement = $this->database->prepare($sql);
        $statement->execute($conditions->getParameters());

        $i18nValues = [];
        while ($row = $statement->fetchArray()) {
            $language = LanguageFactory::getInstance()->getLanguageByCode($row['languageCode']);
            if ($language === null) {
                continue;
            }

            $languageItem = $row['languageItem'];
            if (!isset($i18nValues[$languageItem])) {
                $i18nValues[$languageItem] = [];
            }
            $i18nValues[$languageItem][$language->languageID] = $row['languageItemValue'];
        }

        return $i18nValues;
    }
}
