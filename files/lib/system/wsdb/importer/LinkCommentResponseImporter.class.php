<?php

namespace wcf\system\wsdb\importer;

use wcf\data\comment\response\CommentResponseEditor;
use wcf\system\importer\AbstractCommentResponseImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\WCF;

final class LinkCommentResponseImporter extends AbstractCommentResponseImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'dev.hanashi.wsdb.links.comment';

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['commentID'] = ImportHandler::getInstance()->getNewID($this->objectTypeName, $data['commentID']);
        if (!$data['commentID']) {
            return 0;
        }

        $response = CommentResponseEditor::create($data);

        $sql = "SELECT      responseID
                FROM        wcf1_comment_response
                WHERE       commentID = ?
                ORDER BY    time ASC, responseID ASC";
        $statement = WCF::getDB()->prepare($sql, 5);
        $statement->execute([$response->commentID]);
        $responseIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        // update parent comment
        $sql = "UPDATE  wcf1_comment
                SET     responseIDs = ?,
                        responses = responses + 1
                WHERE   commentID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            \serialize($responseIDs),
            $response->commentID,
        ]);

        return $response->responseID;
    }
}
