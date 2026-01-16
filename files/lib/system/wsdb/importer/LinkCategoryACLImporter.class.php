<?php

namespace wcf\system\wsdb\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractACLImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\WCF;

final class LinkCategoryACLImporter extends AbstractACLImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'dev.hanashi.wsdb.links.category';

    public function __construct()
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.acl',
            'com.woltlab.wsdb.category'
        );
        $this->objectTypeID = $objectType->objectTypeID;

        parent::__construct();
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        if (!isset($this->options[$additionalData['optionName']])) {
            return 0;
        }
        $data['optionID'] = $this->options[$additionalData['optionName']];

        $data['objectID'] = ImportHandler::getInstance()->getNewID($this->objectTypeName, $data['objectID']);
        if (!$data['objectID']) {
            return 0;
        }

        if (!empty($data['groupID'])) {
            $sql = "INSERT IGNORE INTO  wcf1_acl_option_to_group
                                        (optionID, objectID, groupID, optionValue)
                    VALUES              (?, ?, ?, ?)";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$data['optionID'], $data['objectID'], $data['groupID'], $data['optionValue']]);

            return 1;
        } elseif (!empty($data['userID'])) {
            $sql = "INSERT IGNORE INTO  wcf1_acl_option_to_user
                                        (optionID, objectID, userID, optionValue)
                    VALUES              (?, ?, ?, ?)";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$data['optionID'], $data['objectID'], $data['userID'], $data['optionValue']]);

            return 1;
        }
    }
}
