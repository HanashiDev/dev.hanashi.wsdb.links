<?php

use wcf\command\wsdb\database\SetDatabaseLinksLanguageItems;
use wcf\data\wsdb\database\DatabaseList;

$databaseList = new DatabaseList();
$databaseList->readObjects();

foreach ($databaseList as $database) {
    (new SetDatabaseLinksLanguageItems($database))();
}
