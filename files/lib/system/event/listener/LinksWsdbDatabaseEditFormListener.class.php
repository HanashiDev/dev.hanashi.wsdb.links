<?php

namespace wcf\system\event\listener;

use wcf\acp\form\WsdbDatabaseEditForm;
use wcf\data\IStorableObject;
use wcf\data\wsdb\database\Database;
use wcf\data\wsdb\database\language\DatabaseLanguage;
use wcf\data\wsdb\database\language\DatabaseLanguageAction;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

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
            TextFormField::create('linkButton')
                ->label('dev.hanashi.wsdb.linkButtonTitle')
                ->maximumLength(100)
                ->required()
                ->i18nRequired()
                ->addDependency(
                    NonEmptyFormFieldDependency::create('linksMandatoryDependency')
                        ->fieldId('enableLinks')
                ),
        ]);
    }

    protected function onBuildForm(WsdbDatabaseEditForm $eventObj): void
    {
        $eventObj->form->getDataHandler()->addProcessor(
            new CustomFormDataProcessor(
                'linksProcessor',
                static function (IFormDocument $document, array $parameters): array {
                    if (\is_array($parameters['linkButton_i18n'])) {
                        $parameters['language']['linkButton'] = $parameters['linkButton_i18n'];
                    } else {
                        $parameters['language']['linkButton'] = [
                            WCF::getLanguage()->languageID => $parameters['linkButton_i18n'],
                        ];
                    }
                    unset(
                        $parameters['linkButton_i18n'],
                        $parameters['data']['linkButton']
                    );

                    return $parameters;
                },
                static function (IFormDocument $document, array $data, IStorableObject $object) use ($eventObj): array {
                    \assert($object instanceof Database);

                    $field = $eventObj->form->getFormField('linkButton');
                    $field->value($object->getPhrases('linkButton'));

                    return $data;
                }
            )
        );
    }

    protected function onSaved(WsdbDatabaseEditForm $eventObj): void
    {
        $formData = $eventObj->form->getData();

        foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
            $databaseLanguage = DatabaseLanguage::getDatabaseLanguage(
                $eventObj->formObject->databaseID,
                $language->languageID
            );
            if ($databaseLanguage !== null) {
                $action = new DatabaseLanguageAction([$databaseLanguage], 'update', ['data' => [
                    'linkButton' => $formData['language']['linkButton'][$language->languageID] ?? '',
                ]]);
                $action->executeAction();
            }
        }
    }
}
