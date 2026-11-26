<?php

namespace App\Models;

use Atwx\SilverstripeFrontdeskKit\Forms\RecordFiles;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 * A person at a client.
 *
 * @property ?string $FirstName
 * @property ?string $Surname
 * @property ?string $Email
 * @property ?string $Phone
 * @property ?string $Role
 * @property bool $IsPrimary
 * @property int $ClientID
 * @property int $PhotoID
 * @method \App\Models\Client Client()
 * @method \SilverStripe\Assets\Image Photo()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Contact extends DataObject
{
    private static string $table_name = 'Demo_Contact';

    private static string $singular_name = 'Contact';

    private static string $plural_name = 'Contacts';

    private static array $db = [
        'FirstName' => 'Varchar(100)',
        'Surname' => 'Varchar(100)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(50)',
        'Role' => 'Varchar(100)',
        'IsPrimary' => 'Boolean',
    ];

    private static array $has_one = [
        'Client' => Client::class,
        'Photo' => Image::class,
    ];

    private static array $field_labels = [
        'FirstName' => 'First name',
        'IsPrimary' => 'Primary contact',
        'ClientID' => 'Client',
    ];

    private static array $summary_fields = ['FirstName', 'Surname', 'Email'];

    private static string $default_sort = 'Surname ASC, FirstName ASC';

    public function getTitle(): string
    {
        return trim($this->FirstName . ' ' . $this->Surname);
    }

    public function canView($member = null): bool
    {
        return Permission::check('FDK_CLIENTS_VIEW', 'any', $member);
    }

    public function canEdit($member = null): bool
    {
        return Permission::check('FDK_CLIENTS_EDIT', 'any', $member);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canEdit($member);
    }

    protected function onAfterDelete(): void
    {
        parent::onAfterDelete();
        RecordFiles::deleteFiles($this);
    }
}
