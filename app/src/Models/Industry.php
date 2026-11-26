<?php

namespace App\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * Industry of a client, nested (e.g. "Public sector › Education"); used by
 * the tree filter of the client list.
 *
 * @property ?string $Title
 * @property int $ParentID
 * @method \App\Models\Industry Parent()
 * @method \SilverStripe\ORM\DataList|\App\Models\Industry[] Children()
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Industry extends DataObject
{
    private static string $table_name = 'Demo_Industry';

    private static array $db = [
        'Title' => 'Varchar(100)',
    ];

    private static array $has_one = [
        'Parent' => Industry::class,
    ];

    private static array $has_many = [
        'Children' => Industry::class . '.Parent',
    ];

    private static string $default_sort = 'Title ASC';

    public function canView($member = null): bool
    {
        return true;
    }

    /**
     * "Public sector › Education".
     */
    public function getFullTitle(): string
    {
        $parent = $this->Parent();
        return $parent->exists() ? $parent->Title . ' › ' . $this->Title : (string) $this->Title;
    }

    /**
     * IDs of this industry and all below it.
     *
     * @return array<int, int>
     */
    public function withDescendantIDs(): array
    {
        $ids = [(int) $this->ID];
        foreach ($this->Children() as $child) {
            $ids = array_merge($ids, $child->withDescendantIDs());
        }
        return $ids;
    }
}
