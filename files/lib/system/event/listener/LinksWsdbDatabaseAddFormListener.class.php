<?php

namespace wcf\system\event\listener;

use wcf\acp\form\WsdbDatabaseAddForm;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\IFormDocument;

final class LinksWsdbDatabaseAddFormListener extends AbstractEventListener
{
    protected function onBuildForm(WsdbDatabaseAddForm $eventObj): void
    {
        $eventObj->form->getDataHandler()->addProcessor(
            new CustomFormDataProcessor(
                'linksProcessor',
                static function (IFormDocument $document, array $parameters): array {
                    if ($parameters['data']['preset'] == 'dev.hanashi.links') {
                        $parameters['data']['enableLinks'] = 1;
                        $parameters['data']['linksMandatory'] = 1;
                    }

                    return $parameters;
                }
            )
        );
    }
}
