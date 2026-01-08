<?php

use wcf\system\database\table\column\TinyintDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\PartialDatabaseTable;

return [
    PartialDatabaseTable::create('wcf1_wsdb_database')
        ->columns([
            TinyintDatabaseTableColumn::create('enableLinks')
                ->notNull()
                ->defaultValue(0),
            TinyintDatabaseTableColumn::create('linksMandatory')
                ->notNull()
                ->defaultValue(0),
        ]),
    PartialDatabaseTable::create('wcf1_wsdb_record')
        ->columns([
            VarcharDatabaseTableColumn::create('externalUrl')
                ->length(255),
        ]),
];
