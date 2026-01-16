<?php

namespace wcf\system\wsdb\importer;

use wcf\command\wsdb\record\SetRecordOptionValues;
use wcf\data\wsdb\record\Record;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;
use wcf\util\StringUtil;

final class LinkOptionValuesImporter extends AbstractImporter
{
    #[\Override]
    public function import($oldID, array $data, array $additionalData = []): int
    {
        $linkID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.links', $additionalData['linkID']);
        $record = new Record($linkID);
        if (!$record->recordID) {
            return 0;
        }

        $optionData = [];
        foreach ($data as $optionValue) {
            $newOptionID = ImportHandler::getInstance()->getNewID(
                'dev.hanashi.wsdb.links.option',
                $optionValue['optionID']
            );
            if ($newOptionID === null) {
                continue;
            }

            $val = $optionValue['optionValue'];
            if (\in_array($optionValue['optionType'], ['checkboxes', 'multiSelect'])) {
                $val = \implode(',', \explode("\n", StringUtil::unifyNewlines($val)));
            }

            if ($val != '') {
                $optionData['option' . $newOptionID] = $val;
            }
        }

        (new SetRecordOptionValues(
            $record,
            $optionData,
            []
        ))();

        return 0;
    }
}
