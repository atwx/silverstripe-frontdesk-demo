<?php

namespace App\Models;

use Atwx\SilverstripeFrontdeskKit\Forms\RecordFiles;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Permission;

/**
 * A client of the agency, with contacts and projects.
 *
 * @property ?string $Name
 * @property ?string $Website
 * @property ?string $City
 * @property ?string $Country
 * @property bool $IsActive
 * @property ?string $Notes
 * @property int $IndustryID
 * @property int $LogoID
 * @method \App\Models\Industry Industry()
 * @method \SilverStripe\Assets\Image Logo()
 * @method \SilverStripe\ORM\DataList|\App\Models\Contact[] Contacts()
 * @method \SilverStripe\ORM\DataList|\App\Models\Project[] Projects()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Client extends DataObject
{
    private static string $table_name = 'Demo_Client';

    private static string $singular_name = 'Client';

    private static string $plural_name = 'Clients';

    private static array $db = [
        'Name' => 'Varchar(150)',
        'Website' => 'Varchar(255)',
        'City' => 'Varchar(100)',
        'Country' => 'Varchar(100)',
        'IsActive' => 'Boolean',
        'Notes' => 'Text',
    ];

    private static array $has_one = [
        'Industry' => Industry::class,
        'Logo' => Image::class,
    ];

    private static array $has_many = [
        'Contacts' => Contact::class,
        'Projects' => Project::class,
    ];

    private static array $cascade_deletes = ['Contacts', 'Projects'];

    private static array $defaults = ['IsActive' => true];

    private static array $field_labels = [
        'IsActive' => 'Active',
        'Industry' => 'Industry',
        'IndustryID' => 'Industry',
    ];

    private static array $summary_fields = ['Name', 'City', 'Country'];

    private static string $default_sort = 'Name ASC';

    public function getTitle(): string
    {
        return (string) $this->Name;
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
