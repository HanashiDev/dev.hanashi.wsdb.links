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
        'dev.hanashi.wsdb.links.category.acl' => 'CategoryACL',
        'dev.hanashi.wsdb.links.option' => 'Option',
        'dev.hanashi.wsdb.links.links' => 'Links',
        'dev.hanashi.wsdb.links.comment' => 'Comment',
        'dev.hanashi.wsdb.links.comment.response' => 'CommentResponse',
        'dev.hanashi.wsdb.links.bbcode' => 'BBCode',
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
                'dev.hanashi.wsdb.links.comment',
                'dev.hanashi.wsdb.links.comment.response',
                'dev.hanashi.wsdb.links.bbcode',
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
        $sql = "SELECT  *
                FROM    wcf1_user_group";

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
