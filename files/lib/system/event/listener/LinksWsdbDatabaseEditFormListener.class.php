<?php

namespace wcf\system\event\listener;

use wcf\acp\form\WsdbDatabaseEditForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;

final class LinksWsdbDatabaseEditFormListener extends AbstractEventListener
{
    protected function onCreateForm(WsdbDatabaseEditForm $eventObj): void
    {
        $settings = $eventObj->form->getNodeById('settings');
        \assert($settings instanceof FormContainer);

        $settings->appendChildren([
            BooleanFormField::create('enableLinks')
                ->label('dev.hanashi.wsdb.enableLinks'),
            BooleanFormField::create('linksMandatory')
                ->label('dev.hanashi.wsdb.linksMandatory')
                ->addDependency(
                    NonEmptyFormFieldDependency::create('linksMandatoryDependency')
                        ->fieldId('enableLinks')
                ),
        ]);
    }
}
