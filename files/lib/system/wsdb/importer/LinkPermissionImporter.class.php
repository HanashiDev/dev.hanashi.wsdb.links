<?php

namespace wcf\system\wsdb\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractACLImporter;

final class LinkPermissionImporter extends AbstractACLImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'dev.hanashi.wsdb.links';

    public function __construct()
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.acl',
            'com.woltlab.wsdb.database'
        );
        $this->objectTypeID = $objectType->objectTypeID;

        parent::__construct();
    }
}
