<?php

namespace wcf\command\wsdb\database;

use wcf\data\wsdb\database\Database;
use wcf\data\wsdb\database\language\DatabaseLanguage;
use wcf\data\wsdb\database\language\DatabaseLanguageAction;
use wcf\system\language\LanguageFactory;

final class SetDatabaseLinksLanguageItems
{
    public function __construct(private readonly Database $database)
    {
    }

    public function __invoke(): void
    {
        foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
            $databaseLanguage = DatabaseLanguage::getDatabaseLanguage(
                $this->database->databaseID,
                $language->languageID
            );
            if ($databaseLanguage !== null) {
                $action = new DatabaseLanguageAction([$databaseLanguage], 'update', ['data' => [
                    'linkButton' => $language->get('dev.hanashi.wsdb.linkButton'),
                ]]);
                $action->executeAction();
            }
        }
    }
}
