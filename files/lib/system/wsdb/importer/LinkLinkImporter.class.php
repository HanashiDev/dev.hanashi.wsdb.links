<?php

namespace wcf\system\wsdb\importer;

use wcf\command\wsdb\record\SetRecordContent;
use wcf\data\attachment\AttachmentAction;
use wcf\data\file\FileEditor;
use wcf\data\page\Page;
use wcf\data\wsdb\record\Record;
use wcf\data\wsdb\record\RecordAction;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;

final class LinkLinkImporter extends AbstractImporter
{
    #[\Override]
    public function import($oldID, array $data, array $additionalData = []): int
    {
        $databaseID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links', 1);
        $newCategoryID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.category', $data['categoryID']);
        if (!$newCategoryID) {
            return 0;
        }

        $contentData = [
            'title' => $data['subject'],
            'teaser' => '',
            'description' => $data['message'],
            'tags' => $additionalData['tags'],
        ];

        $action = new RecordAction([], 'create', [
            'data' => [
                'databaseID' => $databaseID,
                'categoryID' => $newCategoryID,
                'time' => $data['time'],
                'userID' => $data['userID'],
                'username' => $data['username'],
                'isDisabled' => $data['isDisabled'],
                'comments' => $data['comments'],
                'coverPhotoID' => $this->getCoverPhotoID($data['imageFile']),
                'externalURL' => $this->getLink($data),
            ],
            'content' => [
                0 => $contentData,
            ],
        ]);
        $record = $action->executeAction()['returnValues'];
        \assert($record instanceof Record);

        ImportHandler::getInstance()->saveNewID('dev.hanashi.wsdb.links.links', $oldID, $record->recordID);

        if ($data['attachments']) {
            try {
                $action = new AttachmentAction([], 'copy', [
                    'sourceObjectType' => 'de.pehbeh.links.linkEntry',
                    'targetObjectType' => 'com.woltlab.wsdb.record',
                    'sourceObjectID' => $oldID,
                    'targetObjectID' => $record->recordID,
                ]);
                $action->executeAction()['returnValues'];

                $attachmentHandler = new AttachmentHandler('com.woltlab.wsdb.record', $record->recordID);
                $contentData['attachmentHandler'] = $attachmentHandler;
            } catch (\Throwable) {
            }
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($data['message'], 'com.woltlab.wsdb.record', $record->recordID);
        $contentData['htmlInputProcessor'] = $htmlInputProcessor;

        (new SetRecordContent($record, [0 => $contentData]))();

        return $record->recordID;
    }

    private function getCoverPhotoID(?string $imageFile): ?int
    {
        if (!$imageFile) {
            return null;
        }

        $pathname = WCF_DIR . 'images/links/' . $imageFile;
        if (!\file_exists($pathname)) {
            return null;
        }

        $file = FileEditor::createFromExistingFile($pathname, $imageFile, 'com.woltlab.wsdb.coverPhoto', true);
        if ($file === null) {
            return null;
        }

        return $file->fileID;
    }

    /**
     * @param array<mixed> $data
     */
    private function getLink(array $data): string
    {
        if (empty($data['pageID'])) {
            return $data['url'];
        }

        $page = new Page($data['pageID']);

        return $page->getLink();
    }
}
