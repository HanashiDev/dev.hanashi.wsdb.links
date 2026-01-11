<?php

use wcf\event\wsdb\database\DatabaseCreated;
use wcf\event\wsdb\database\preset\PresetCollecting;
use wcf\system\event\EventHandler;
use wcf\system\event\listener\LinksWsdbDatabaseCreatedListener;
use wcf\system\wsdb\database\preset\LinkPreset;

return static function (): void {
    EventHandler::getInstance()->register(
        PresetCollecting::class,
        static function (PresetCollecting $event): void {
            $event->register(new LinkPreset());
        }
    );

    EventHandler::getInstance()->register(
        DatabaseCreated::class,
        LinksWsdbDatabaseCreatedListener::class
    );
};
