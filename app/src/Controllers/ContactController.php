<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Contact;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskController;
use Atwx\SilverstripeFrontdeskKit\Filter\FilterCollection;
use Atwx\SilverstripeFrontdeskKit\Filter\SelectFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\TextFilter;
use Atwx\SilverstripeFrontdeskKit\Forms\FieldLayout;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use Atwx\SilverstripeFrontdeskKit\Table\RowAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * All contacts: a photo upload in the form, row actions with icons.
 *
 */
class ContactController extends FrontdeskController
{
    private static string $managed_model = Contact::class;

    private static string $url_segment = 'contacts';

    private static string $title = 'Contacts';

    private static string $permission_code = 'FDK_CLIENTS';

    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Title', 'Name')->link('view/{ID}')->sortable('Surname')->end()
            ->make('Client.Name', 'Client')->link('clients/view/{ClientID}')->sortable('Client.Name')->end()
            ->make('Role', 'Role')->end()
            ->make('Email', 'Email')->end()
            ->make('Phone', 'Phone')->end();
    }

    protected function defineFilters(): FilterCollection
    {
        return FilterCollection::create()
            ->add(TextFilter::create('Query', 'Search')
                ->apply(fn (DataList $list, $value) => $list->filterAny([
                    'FirstName:PartialMatch' => $value,
                    'Surname:PartialMatch' => $value,
                    'Email:PartialMatch' => $value,
                ])))
            ->add(SelectFilter::create('Client', 'Client')
                ->options(fn () => Client::get()->sort('Name')->map('ID', 'Name')->toArray())
                ->apply(fn (DataList $list, $value) => $list->filter('ClientID', (int) $value)));
    }

    // [demo:rowactions]
    protected function defineRowActions(DataObject $record): array
    {
        /** @var Contact $record */
        $actions = [
            RowAction::link('Send mail', 'mailto:' . $record->Email)
                ->withIcon('mail')
                ->direct()
                ->enabled((bool) $record->Email),
            RowAction::link('Call', 'tel:' . preg_replace('/[^0-9+]/', '', (string) $record->Phone))
                ->withIcon('phone')
                ->direct()
                ->enabled((bool) $record->Phone),
            RowAction::link('View', $this->Link('view/' . $record->ID)),
        ];
        if ($this->canEdit()) {
            $actions[] = RowAction::edit($this->Link('edit/' . $record->ID));
            $actions[] = RowAction::delete($this->Link('delete/' . $record->ID));
        }
        return $actions;
    }
    // [/demo:rowactions]

    // [demo:files]
    protected function defineFieldLayout(?DataObject $record): ?FieldLayout
    {
        return FieldLayout::create()
            ->group('Person', ['FirstName', 'Surname', 'PhotoID'], true, 'person')
            ->group('Contact', ['Email', 'Phone', 'Role', 'ClientID', 'IsPrimary'], true, 'contact');
    }

    protected function formFields(FieldList $fields): FieldList
    {
        // Photo is a has_one Image: the kit puts an upload field here.
        $fields->dataFieldByName('PhotoID')
            ?->setAllowedExtensions(['jpg', 'jpeg', 'png', 'webp'])
            ->setMaxFileSize('2M');
        return $fields;
    }
    // [/demo:files]
}
