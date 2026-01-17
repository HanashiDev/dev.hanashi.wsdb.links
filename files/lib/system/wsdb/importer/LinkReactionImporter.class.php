<?php

namespace wcf\system\wsdb\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractLikeImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;

final class LinkReactionImporter extends AbstractLikeImporter
{
    public function __construct()
    {
        $databaseID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links', 1);
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.like.likeableObject',
            'com.woltlab.wsdb.db' . $databaseID . '.reaction'
        );
        $this->objectTypeID = $objectType->objectTypeID;
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['objectID'] = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.links', $data['objectID']);
        if (!$data['objectID']) {
            return 0;
        }

        if (empty($data['time'])) {
            $data['time'] = 1;
        }

        if (!isset($data['reactionTypeID'])) {
            if ($data['likeValue'] == 1) {
                $data['reactionTypeID'] = ReactionHandler::getInstance()->getFirstReactionTypeID();
            } else {
                $data['reactionTypeID'] = self::getDislikeReactionTypeID();
            }
        } else {
            $data['reactionTypeID'] = ImportHandler::getInstance()
                ->getNewID('com.woltlab.wcf.reactionType', $data['reactionTypeID']);
        }

        if (empty($data['reactionTypeID'])) {
            return 0;
        }

        $sql = "INSERT IGNORE INTO  wcf1_like
                                    (objectID, objectTypeID, objectUserID, userID, time, likeValue, reactionTypeID)
                VALUES              (?, ?, ?, ?, ?, ?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            $data['objectID'],
            $this->objectTypeID,
            $data['objectUserID'],
            $data['userID'],
            $data['time'],
            $data['likeValue'],
            $data['reactionTypeID'],
        ]);

        return 0;
    }
}
