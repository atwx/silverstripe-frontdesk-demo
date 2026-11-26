<?php

namespace App\Controllers;

use App\Models\Contact;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskSubController;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use SilverStripe\ORM\DataList;

/**
 * Contacts of a client (page "Contacts" of the client).
 *
 */
class ContactSubController extends FrontdeskSubController
{
    private static string $managed_model = Contact::class;

    private static string $url_segment = 'contacts';

    private static string $title = 'Contacts';

    private static string $parent_field = 'ClientID';

    protected function getBaseQuery(): DataList
    {
        return Contact::get()->filter('ClientID', $this->getParentID());
    }

    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Title', 'Name')->sortable('Surname')->end()
            ->make('Role', 'Role')->end()
            ->make('Email', 'Email')->end()
            ->make('Phone', 'Phone')->end()
            ->make('IsPrimary', 'Primary')->format(fn ($value) => $value ? '★' : '')->end();
    }
}
