<?php

namespace wcf\system\wsdb\importer;

use wcf\data\comment\CommentEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractCommentImporter;
use wcf\system\importer\ImportHandler;

final class LinkCommentImporter extends AbstractCommentImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'dev.hanashi.wsdb.links.comment';

    public function __construct()
    {
        $databaseID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links', 1);
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.comment.commentableContent',
            'com.woltlab.wsdb.db' . $databaseID . '.comment'
        );
        $this->objectTypeID = $objectType->objectTypeID;
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $recordID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.links', $data['objectID']);
        if (!$recordID) {
            return 0;
        }
        $data['objectID'] = $recordID;

        $comment = CommentEditor::create(\array_merge($data, ['objectTypeID' => $this->objectTypeID]));

        ImportHandler::getInstance()->saveNewID($this->objectTypeName, $oldID, $comment->commentID);

        return $comment->commentID;
    }
}
