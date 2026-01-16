<?php

namespace wcf\system\wsdb\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractCategoryImporter;
use wcf\system\importer\ImportHandler;

final class LinkCategoryImporter extends AbstractCategoryImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'dev.hanashi.wsdb.links.category';

    public function __construct()
    {
        $databaseID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links', 1);
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.category',
            'com.woltlab.wsdb.db' . $databaseID
        );
        $this->objectTypeID = $objectType->objectTypeID;
    }
}
