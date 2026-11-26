<?php

namespace App\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * A task of a project.
 *
 * @property ?string $Title
 * @property ?string $Status
 * @property ?string $DueDate
 * @property float $Estimate
 * @property int $ProjectID
 * @property int $AssigneeID
 * @method \App\Models\Project Project()
 * @method \SilverStripe\Security\Member Assignee()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Task extends DataObject
{
    public const array STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'done' => 'Done',
    ];

    private static string $table_name = 'Demo_Task';

    private static string $singular_name = 'Task';

    private static string $plural_name = 'Tasks';

    private static array $db = [
        'Title' => 'Varchar(200)',
        'Status' => "Enum('open,in_progress,done', 'open')",
        'DueDate' => 'Date',
        'Estimate' => 'Decimal(5,1)',
    ];

    private static array $has_one = [
        'Project' => Project::class,
        'Assignee' => Member::class,
    ];

    private static array $field_labels = [
        'DueDate' => 'Due',
        'Estimate' => 'Estimate (h)',
        'AssigneeID' => 'Assignee',
        'ProjectID' => 'Project',
    ];

    private static array $summary_fields = ['Title', 'Status', 'DueDate'];

    private static string $default_sort = 'DueDate ASC, ID ASC';

    public function getStatusLabel(): string
    {
        return self::STATUSES[(string) $this->Status] ?? (string) $this->Status;
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
