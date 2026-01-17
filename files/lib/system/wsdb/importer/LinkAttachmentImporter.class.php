<?php

namespace wcf\system\wsdb\importer;

use wcf\command\wsdb\record\SetRecordContent;
use wcf\data\attachment\AttachmentAction;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\wsdb\record\Record;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\importer\AbstractAttachmentImporter;
use wcf\system\importer\ImportHandler;

final class LinkAttachmentImporter extends AbstractAttachmentImporter
{
    public function __construct()
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.attachment.objectType',
            'com.woltlab.wsdb.record'
        );
        $this->objectTypeID = $objectType->objectTypeID;
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $recordID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.links', $data['linkID']);
        $record = new Record($recordID);
        if (!$record->recordID) {
            return 0;
        }
        $content = $record->getRecordContent();
        if ($content === null) {
            return 0;
        }

        try {
            $action = new AttachmentAction([], 'copy', [
                'sourceObjectType' => 'de.pehbeh.links.linkEntry',
                'targetObjectType' => 'com.woltlab.wsdb.record',
                'sourceObjectID' => $data['linkID'],
                'targetObjectID' => $content->contentID,
            ]);
            $returnValues = $action->executeAction()['returnValues'];
            $attachmentIDs = $returnValues['attachmentIDs'];

            $recordContent = $content->getData();
            foreach ($attachmentIDs as $oldAttachmentID => $attachmentID) {
                if (
                    (
                        $newMessage = $this->fixEmbeddedAttachments(
                            $recordContent['description'],
                            $oldAttachmentID,
                            $attachmentID
                        )
                    ) !== false
                ) {
                    $recordContent['description'] = $newMessage;
                }
            }

            $htmlInputProcessor = new HtmlInputProcessor();
            $htmlInputProcessor->process($recordContent['description'], 'com.woltlab.wsdb.record', $content->contentID);
            $recordContent['htmlInputProcessor'] = $htmlInputProcessor;

            (new SetRecordContent($record, [0 => $recordContent]))();
        } catch (\Throwable) {
        }

        return 0;
    }
}
