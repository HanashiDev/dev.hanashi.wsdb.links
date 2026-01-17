<?php

namespace wcf\system\wsdb\importer;

use wcf\command\wsdb\database\SetDatabaseLanguage;
use wcf\data\wsdb\database\Database;
use wcf\data\wsdb\database\DatabaseAction;
use wcf\data\wsdb\database\DatabaseList;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

final class LinkDatabaseImporter extends AbstractImporter
{
    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $path = $this->getAvailablePath();
        $action = new DatabaseAction([], 'create', [
            'data' => [
                'name' => WCF::getLanguage()->get('wsdb.preset.dev.hanashi.links'),
                'path' => $path,
                'preset' => 'dev.hanashi.links',
                'isMultiLingual' => 0,
                'isDisabled' => 0,
                'enableLinks' => 1,
                'linksMandatory' => 1,
            ],
        ]);
        $database = $action->executeAction()['returnValues'];
        \assert($database instanceof Database);

        ImportHandler::getInstance()->resetMapping();
        ImportHandler::getInstance()->saveNewID('dev.hanashi.wsdb.links', $oldID, $database->databaseID);

        $this->setLanguage($database);

        return $database->databaseID;
    }

    private function setLanguage(Database $database): void
    {
        $languageData = [];
        foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
            $languageData['recordSingular'][$language->languageID] = $language->get('dev.hanashi.wsdb.links.recordSingular');
            $languageData['recordPlural'][$language->languageID] = $language->get('dev.hanashi.wsdb.links.recordPlural');
            $languageData['definiteArticle'][$language->languageID] = $language->get('dev.hanashi.wsdb.links.definiteArticle');
        }

        (new SetDatabaseLanguage($database, $languageData))();
    }

    private function getAvailablePath(?int $suffix = null): string
    {
        $path = 'links';
        if ($suffix !== null) {
            $path .= (string)$suffix;
        }

        $databaseList = new DatabaseList();
        $databaseList->getConditionBuilder()->add('path = ?', [$path]);

        if (!$databaseList->countObjects()) {
            return $path;
        }

        $newSuffix = 1;
        if ($suffix !== null) {
            $newSuffix = $suffix + $newSuffix;
        }

        return $this->getAvailablePath($newSuffix);
    }
}
