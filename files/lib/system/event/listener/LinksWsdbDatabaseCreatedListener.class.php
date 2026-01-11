<?php

namespace wcf\system\event\listener;

use wcf\command\wsdb\database\SetDatabaseLinksLanguageItems;
use wcf\event\wsdb\database\DatabaseCreated;

final class LinksWsdbDatabaseCreatedListener extends AbstractEventListener
{
    public function __invoke(DatabaseCreated $event): void
    {
        (new SetDatabaseLinksLanguageItems($event->database))();
    }
}
