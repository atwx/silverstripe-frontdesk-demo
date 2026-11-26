<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Industry;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskController;
use Atwx\SilverstripeFrontdeskKit\Filter\FilterCollection;
use Atwx\SilverstripeFrontdeskKit\Filter\SelectFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\TextFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\TreeFilter;
use Atwx\SilverstripeFrontdeskKit\Forms\FieldLayout;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use Atwx\SilverstripeFrontdeskKit\View\RecordPage;
use Atwx\SilverstripeFrontdeskKit\View\RecordPages;
use Atwx\SilverstripeFrontdeskKit\View\RecordSwitcher;
use Atwx\SilverstripeFrontdeskKit\Forms\RecordFiles;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * Clients: grouped by country, filtered by an industry tree; a client opens
 * with record pages in a sidebar and a searchable switcher to the others.
 *
 */
class ClientController extends FrontdeskController
{
    private static string $managed_model = Client::class;

    private static string $url_segment = 'clients';

    private static string $title = 'Clients';

    // [demo:recent]
    private static $recent_views = 20;

    /**
     * The list starts with the clients the member opened last.
     */
    private static bool $recent_first = true;
    // [/demo:recent]

    private static array $sub_controllers = [
        ContactSubController::class,
    ];

    private static array $allowed_actions = [
        'contacts',
    ];

    public function contacts(HTTPRequest $request): ContactSubController
    {
        return ContactSubController::create()->setParentContext($this, (int) $request->param('ID'));
    }

    // [demo:grouped]
    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Name', 'Client')->link('view/{ID}')->sortable()->end()
            ->make('City', 'City')->sortable()->end()
            ->make('Industry.FullTitle', 'Industry')->end()
            ->make('Projects.Count', 'Projects')->end()
            ->make('IsActive', 'Active')->format(fn ($value) => $value ? 'yes' : 'no')->end()
            ->lastViewed('Last viewed')->optional()->end()
            ->groupBy('Country', 'No country');
    }
    // [/demo:grouped]

    // [demo:tree]
    protected function defineFilters(): FilterCollection
    {
        return FilterCollection::create()
            ->add(TextFilter::create('Query', 'Search')
                ->apply(fn (DataList $list, $value) => $list->filterAny([
                    'Name:PartialMatch' => $value,
                    'City:PartialMatch' => $value,
                ])))
            ->add(TreeFilter::create('Industry', 'Industry')
                ->options(fn () => Industry::get()->filter('ParentID', 0))
                ->apply(function (DataList $list, array $ids) {
                    // A chosen industry includes the ones below it.
                    $all = [];
                    foreach (Industry::get()->byIDs($ids) as $industry) {
                        $all = array_merge($all, $industry->withDescendantIDs());
                    }
                    return $list->filter('IndustryID', $all ?: [0]);
                }))
            ->add(SelectFilter::create('Active', 'Active')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->apply(fn (DataList $list, $value) => $list->filter('IsActive', (int) $value)));
    }
    // [/demo:tree]

    protected function defineFieldLayout(?DataObject $record): ?FieldLayout
    {
        return FieldLayout::create()
            ->group('Client', ['Name', 'IndustryID', 'IsActive', 'LogoID'], true, 'basics')
            ->group('Address', ['Website', 'City', 'Country'], true, 'address')
            ->group('Notes', ['Notes'], true, 'notes');
    }

    protected function formFields(FieldList $fields): FieldList
    {
        $options = [];
        foreach (Industry::get()->sort('ParentID, Title') as $industry) {
            $options[(int) $industry->ID] = $industry->getFullTitle();
        }
        asort($options);
        $fields->replaceField('IndustryID', DropdownField::create('IndustryID', 'Industry', $options)
            ->setEmptyString('–'));
        return $fields;
    }

    // [demo:sidebar]
    protected function defineRecordPages(DataObject $record): ?RecordPages
    {
        return RecordPages::create()
            ->page(RecordPage::create('overview', 'Overview')->groups(['basics', 'address']))
            ->page(RecordPage::create('contacts', 'Contacts')->sublist(ContactSubController::class))
            ->page(RecordPage::create('notes', 'Notes')->groups(['notes']))
            ->separator()
            ->switcher($this->clientSwitcher($record));
    }

    /**
     * All clients, searchable, each linking to the page that is open.
     */
    private function clientSwitcher(DataObject $record): RecordSwitcher
    {
        $segment = (string) $this->getRequest()->param('OtherID');
        $switcher = RecordSwitcher::create()->searchable();
        foreach (Client::get()->sort('Name') as $client) {
            $switcher->option(
                (string) $client->Name,
                $this->Link('view/' . $client->ID . ($segment !== '' ? '/' . $segment : '')),
                $client->ID === $record->ID
            );
        }
        return $switcher;
    }

    protected function defineHeaderFields(DataObject $record): ?ArrayList
    {
        /** @var Client $record */
        return ArrayList::create([
            ArrayData::create([
                'Label' => 'Industry',
                'Value' => $record->Industry()->exists() ? $record->Industry()->getFullTitle() : '–',
            ]),
            ArrayData::create(['Label' => 'Projects', 'Value' => (string) $record->Projects()->count()]),
        ]);
    }

    protected function defineRecordImage(DataObject $record): ?string
    {
        return RecordFiles::fileOf($record, 'Logo') ? $this->Link('file/' . $record->ID . '/Logo') : null;
    }
    // [/demo:sidebar]

    // [demo:cards]
    /**
     * A client on a project's card: where it is and its primary contact.
     */
    protected function defineCompactFields(DataObject $record): ArrayList
    {
        /** @var Client $record */
        $rows = ArrayList::create([
            ArrayData::create([
                'Label' => 'City',
                'Value' => trim($record->City . ', ' . $record->Country, ', '),
                'Type' => 'text',
            ]),
        ]);
        $contact = $record->Contacts()->filter('IsPrimary', true)->first();
        if ($contact) {
            $rows->push(ArrayData::create([
                'Label' => 'Contact',
                'Value' => sprintf(
                    '%s<br><a class="link" href="mailto:%s">%s</a>',
                    htmlspecialchars($contact->getTitle()),
                    htmlspecialchars((string) $contact->Email),
                    htmlspecialchars((string) $contact->Email)
                ),
                'Type' => 'html',
            ]));
        }
        return $rows;
    }
    // [/demo:cards]
}
