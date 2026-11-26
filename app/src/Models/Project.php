<?php

namespace App\Models;

use Atwx\SilverstripeFrontdeskKit\Catalog\CatalogEntry;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * A project for a client, with tasks, notes (timeline) and client feedback
 * collected through a link without login.
 *
 * @property ?string $Title
 * @property ?string $Status
 * @property ?string $Priority
 * @property float $Budget
 * @property ?string $StartDate
 * @property ?string $EndDate
 * @property ?string $Description
 * @property ?string $InternalNote
 * @property ?string $FeedbackToken
 * @property int $FeedbackRating
 * @property ?string $FeedbackText
 * @property ?string $FeedbackAt
 * @property int $ClientID
 * @property int $ManagerID
 * @property int $TypeID
 * @method \App\Models\Client Client()
 * @method \SilverStripe\Security\Member Manager()
 * @method \Atwx\SilverstripeFrontdeskKit\Catalog\CatalogEntry Type()
 * @method \SilverStripe\ORM\DataList|\App\Models\Task[] Tasks()
 * @method \SilverStripe\ORM\DataList|\App\Models\ProjectNote[] Notes()
 * @method \SilverStripe\ORM\ManyManyList|\Atwx\SilverstripeFrontdeskKit\Catalog\CatalogEntry[] Tags()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Project extends DataObject
{
    public const array STATUSES = [
        'lead' => 'Lead',
        'active' => 'Active',
        'on_hold' => 'On hold',
        'done' => 'Done',
    ];

    public const array PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
    ];

    private static string $table_name = 'Demo_Project';

    private static string $singular_name = 'Project';

    private static string $plural_name = 'Projects';

    private static array $db = [
        'Title' => 'Varchar(150)',
        'Status' => "Enum('lead,active,on_hold,done', 'lead')",
        'Priority' => "Enum('low,normal,high', 'normal')",
        'Budget' => 'Decimal(10,2)',
        'StartDate' => 'Date',
        'EndDate' => 'Date',
        'Description' => 'Text',
        'InternalNote' => 'Text',
        'FeedbackToken' => 'Varchar(64)',
        'FeedbackRating' => 'Int',
        'FeedbackText' => 'Text',
        'FeedbackAt' => 'Datetime',
    ];

    private static array $has_one = [
        'Client' => Client::class,
        'Manager' => Member::class,
        'Type' => CatalogEntry::class,
    ];

    private static array $has_many = [
        'Tasks' => Task::class,
        'Notes' => ProjectNote::class,
    ];

    // [demo:catalogfields]
    private static array $many_many = [
        'Tags' => CatalogEntry::class,
    ];

    private static array $cascade_deletes = ['Tasks', 'Notes'];

    /**
     * Catalog fields: relation => catalog key (app/_config/catalogs.yml).
     */
    private static array $catalog_fields = [
        'Type' => 'project_type',
        'Tags' => 'tags',
    ];
    // [/demo:catalogfields]

    /**
     * Never in forms and views: the token of the feedback link.
     */
    private static array $fdk_hidden_fields = ['FeedbackToken'];

    private static array $field_labels = [
        'ClientID' => 'Client',
        'ManagerID' => 'Manager',
        'TypeID' => 'Type',
        'StartDate' => 'Start',
        'EndDate' => 'End',
        'InternalNote' => 'Internal note',
        'FeedbackRating' => 'Rating (1–5)',
        'FeedbackText' => 'Feedback',
        'FeedbackAt' => 'Feedback given',
    ];

    private static array $summary_fields = ['Title', 'Client.Name', 'Status'];

    private static string $default_sort = 'StartDate DESC, Title ASC';

    public function getStatusLabel(): string
    {
        return self::STATUSES[(string) $this->Status] ?? (string) $this->Status;
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[(string) $this->Priority] ?? (string) $this->Priority;
    }

    /**
     * Share of done tasks in percent, null without tasks.
     */
    public function getProgress(): ?int
    {
        $total = $this->Tasks()->count();
        if (!$total) {
            return null;
        }
        return (int) round(100 * $this->Tasks()->filter('Status', 'done')->count() / $total);
    }

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        if (!$this->FeedbackToken) {
            $this->FeedbackToken = bin2hex(random_bytes(16));
        }
    }

    public function canView($member = null): bool
    {
        return Permission::check('FDK_PROJECTS_VIEW', 'any', $member);
    }

    public function canEdit($member = null): bool
    {
        return Permission::check('FDK_PROJECTS_EDIT', 'any', $member);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canEdit($member);
    }
}
