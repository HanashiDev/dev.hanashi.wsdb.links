<?php

namespace wcf\system\wsdb\importer;

use wcf\data\wsdb\option\Option;
use wcf\data\wsdb\option\OptionAction;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\WCF;
use wcf\util\JSON;
use wcf\util\StringUtil;

final class LinkOptionsImporter extends AbstractImporter
{
    /**
     * @var array<string, string>
     */
    private array $optionTypeMap = [
        'boolean' => 'boolean',
        'checkboxes' => 'checkboxes',
        'date' => 'date',
        'integer' => 'integer',
        'float' => 'float',
        'multiSelect' => 'checkboxes',
        'radioButton' => 'radioButton',
        'select' => 'select',
        'text' => 'text',
        'textarea' => 'textarea',
        'URL' => 'url',
    ];

    #[\Override]
    public function import($oldID, array $data, array $additionalData = []): int
    {
        $databaseID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links', 1);
        if (!$databaseID) {
            return 0;
        }

        if (!isset($this->optionTypeMap[$data['optionType']])) {
            return 0;
        }

        $configuration = [];
        if ($data['required']) {
            $configuration['required'] = 1;
        }
        if (!empty($data['selectOptions'])) {
            $selectOptions = [];
            $lines = \explode("\n", StringUtil::unifyNewlines($data['selectOptions']));
            foreach ($lines as $line) {
                $lineSplitted = \explode(':', $line, 2);
                if (\count($lineSplitted) == 2) {
                    $selectOptions[] = [
                        'key' => $lineSplitted[0],
                        'value' => [$lineSplitted[1]],
                    ];
                } else {
                    $selectOptions[] = [
                        'key' => $lineSplitted[0],
                        'value' => [$lineSplitted[0]],
                    ];
                }
            }
            $configuration['selectOptions'] = JSON::encode($selectOptions);
        }

        $action = new OptionAction([], 'create', [
            'data' => [
                'databaseID' => $databaseID,
                'showOrder' => $data['showOrder'],
                'name' => $data['optionTitle'],
                'description' => $data['optionDescription'],
                'optionType' => $this->optionTypeMap[$data['optionType']],
                'configuration' => JSON::encode($configuration),
                'isDisabled' => $data['isDisabled'],
                'isLimitedToCategories' => $additionalData['categories'] === [] ? 0 : 1,
            ],
        ]);
        $newOption = $action->executeAction()['returnValues'];
        \assert($newOption instanceof Option);

        ImportHandler::getInstance()->saveNewID('dev.hanashi.wsdb.links.option', $oldID, $newOption->optionID);

        $sql = "INSERT INTO wcf1_wsdb_option_to_record_category
                            (optionID, categoryID)
                VALUES      (?, ?)";
        $statement = WCF::getDB()->prepare($sql);

        foreach ($additionalData['categories'] as $categoryID) {
            $newCategoryID = ImportHandler::getInstance()->getNewID('dev.hanashi.wsdb.links.category', $categoryID);
            if (!$newCategoryID) {
                continue;
            }

            $statement->execute([$newOption->optionID, $newCategoryID]);
        }

        return $newOption->optionID;
    }
}
