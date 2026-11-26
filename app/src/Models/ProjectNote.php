<?php

namespace App\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * An entry of a project's timeline, written by record actions ("Change
 * status", "Add note") and by the client's feedback.
 *
 * @property ?string $Kind status, note or feedback
 * @property ?string $Text status, note or feedback
 * @property int $ProjectID status, note or feedback
 * @property int $AuthorID status, note or feedback
 * @method \App\Models\Project Project()
 * @method \SilverStripe\Security\Member Author()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class ProjectNote extends DataObject
{
    private static string $table_name = 'Demo_ProjectNote';

    private static array $db = [
        'Kind' => "Enum('status,note,feedback', 'note')",
        'Text' => 'Text',
    ];

    private static array $has_one = [
        'Project' => Project::class,
        'Author' => Member::class,
    ];

    private static string $default_sort = 'Created DESC, ID DESC';
}
