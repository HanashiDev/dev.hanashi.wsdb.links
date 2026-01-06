<?php

namespace wcf\system\event\listener;

use wcf\form\WsdbRecordAddForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\UrlFormField;

final class LinksWsdbRecordAddFormListener extends AbstractEventListener
{
    protected function onCreateForm(WsdbRecordAddForm $eventObj): void
    {
        if (!$eventObj->getDatabase()->enableLinks) {
            return;
        }

        $generalSection = $eventObj->form->getNodeById('generalSection');
        \assert($generalSection instanceof FormContainer);

        $generalSection->appendChild(
            UrlFormField::create('externalUrl')
                ->label('dev.hanashi.wsdb.externalUrl')
                ->required($eventObj->getDatabase()->linksMandatory)
        );
    }
}
