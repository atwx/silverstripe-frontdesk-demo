<?php

namespace App\Demo;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Task;
use Atwx\SilverstripeFrontdeskKit\Catalog\CatalogEntry;
use Atwx\SilverstripeFrontdeskKit\Catalog\Catalogs;
use Atwx\SilverstripeFrontdeskKit\Model\LastView;
use Atwx\SilverstripeFrontdeskKit\Model\ListColumnChoice;
use Atwx\SilverstripeFrontdeskKit\Model\RecentView;
use Atwx\SilverstripeFrontdeskKit\Model\SavedListView;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

/**
 * Sample data of the demo: industries, catalogs, the team (with the shared
 * demo account), clients, contacts, projects, tasks and notes. seed() only
 * adds what is missing; reset() removes all data and what visitors left
 * (saved views, column choices, uploads) and seeds again.
 */
class DemoSeeder
{
    use Injectable;

    public const string DEMO_EMAIL = 'demo@frontdesk-demo.test';

    public const string DEMO_PASSWORD = 'Try-Frontdesk-2026';

    /**
     * Permissions of the group "Team".
     */
    private const array PERMISSIONS = [
        'FDK_CLIENTS_VIEW', 'FDK_CLIENTS_EDIT',
        'FDK_PROJECTS_VIEW', 'FDK_PROJECTS_EDIT',
        'FDK_CATALOGS_VIEW', 'FDK_CATALOGS_EDIT',
    ];

    private const array COLLEAGUES = [
        ['Alex', 'Morgan'], ['Sam', 'Lee'], ['Kim', 'Novak'], ['Robin', 'Patel'],
    ];

    /**
     * @var array<int, string>
     */
    private array $log = [];

    /**
     * Data of the demo; only what is missing is created.
     *
     * @return array<int, string>
     */
    public function seed(): array
    {
        $this->log = [];
        $site = SiteConfig::current_site_config();
        if ($site->Title !== 'frontdesk-kit demo') {
            $site->Title = 'frontdesk-kit demo';
            $site->write();
        }
        $team = $this->team();
        $this->catalogs();
        if (!Client::get()->exists()) {
            mt_srand(42);
            $industries = $this->industries();
            $this->clients($industries, $team);
        }
        return $this->log;
    }

    /**
     * Removes all demo data and what visitors changed, then seeds again.
     *
     * @return array<int, string>
     */
    public function reset(): array
    {
        foreach ([Task::class, ProjectNote::class, Project::class, Contact::class, Client::class, Industry::class,
            SavedListView::class, ListColumnChoice::class, RecentView::class, LastView::class] as $class) {
            foreach ($class::get() as $record) {
                $record->delete();
            }
        }
        foreach (CatalogEntry::get()->filter('Catalog', ['project_type', 'tags']) as $entry) {
            $entry->delete();
        }
        Versioned::withVersionedMode(function () {
            Versioned::set_stage(Versioned::DRAFT);
            $folder = Folder::find('frontdesk');
            if ($folder) {
                foreach (File::get()->filter('ParentID', $folder->ID) as $file) {
                    $file->deleteFromStage(Versioned::DRAFT);
                }
            }
        });
        DemoAccount::resetting(function () {
            $demo = Member::get()->find('Email', self::DEMO_EMAIL);
            $demo?->delete();
        });
        return array_merge(['Demo data removed'], $this->seed());
    }

    /**
     * Group "Team" with the demo account and four colleagues (who cannot log in).
     *
     * @return array<int, Member>
     */
    private function team(): array
    {
        $group = Group::get()->find('Code', 'team') ?? Group::create(['Title' => 'Team', 'Code' => 'team']);
        if (!$group->isInDB()) {
            $group->write();
            $this->log[] = 'Created group Team';
        }
        foreach (self::PERMISSIONS as $code) {
            Permission::grant($group->ID, $code);
        }
        $members = [];
        DemoAccount::resetting(function () use ($group, &$members) {
            $demo = Member::get()->find('Email', self::DEMO_EMAIL);
            if (!$demo) {
                $demo = Member::create(['FirstName' => 'Demo', 'Surname' => 'User', 'Email' => self::DEMO_EMAIL]);
                $demo->write();
                $result = $demo->changePassword(self::DEMO_PASSWORD);
                $this->log[] = $result->isValid()
                    ? 'Created the demo account'
                    : 'ERROR: demo password refused: ' . implode(' ', array_column($result->getMessages(), 'message'));
            }
            $demo->Groups()->add($group);
            $members[] = $demo;
            foreach (self::COLLEAGUES as [$first, $last]) {
                $email = strtolower($first . '.' . $last) . '@frontdesk-demo.test';
                $member = Member::get()->find('Email', $email);
                if (!$member) {
                    $member = Member::create(['FirstName' => $first, 'Surname' => $last, 'Email' => $email]);
                    $member->write();
                }
                $member->Groups()->add($group);
                $members[] = $member;
            }
        });
        return $members;
    }

    private function catalogs(): void
    {
        $catalogs = Catalogs::singleton();
        foreach ([
            'project_type' => [
                'website' => ['Title' => 'Website', 'IsDefault' => true],
                'app' => 'App',
                'branding' => 'Branding',
                'consulting' => 'Consulting',
                'print' => ['Title' => 'Print', 'Active' => false],
            ],
            'tags' => [
                'fixed' => 'Fixed price',
                'retainer' => 'Retainer',
                'urgent' => 'Urgent',
                'pro_bono' => 'Pro bono',
                'accessibility' => 'Accessibility',
            ],
        ] as $key => $entries) {
            if (!CatalogEntry::get()->filter('Catalog', $key)->exists()) {
                $catalogs->sync($key, $entries);
                $this->log[] = "Created catalog {$key}";
            }
        }
    }

    /**
     * @return array<int, Industry>
     */
    private function industries(): array
    {
        $leaves = [];
        foreach ([
            'Public sector' => ['Education', 'Culture', 'Administration'],
            'Business' => ['Retail', 'Manufacturing', 'Finance', 'Tourism'],
            'Non-profit' => ['Charities', 'Associations'],
        ] as $parentTitle => $children) {
            $parent = Industry::create(['Title' => $parentTitle]);
            $parent->write();
            foreach ($children as $title) {
                $child = Industry::create(['Title' => $title, 'ParentID' => $parent->ID]);
                $child->write();
                $leaves[] = $child;
            }
        }
        return $leaves;
    }

    /**
     * @param array<int, Industry> $industries
     * @param array<int, Member> $team
     */
    private function clients(array $industries, array $team): void
    {
        $cities = [
            ['London', 'United Kingdom'], ['Manchester', 'United Kingdom'], ['Bristol', 'United Kingdom'],
            ['Hamburg', 'Germany'], ['Berlin', 'Germany'], ['Munich', 'Germany'],
            ['Amsterdam', 'Netherlands'], ['Utrecht', 'Netherlands'], ['Lyon', 'France'], ['Paris', 'France'],
        ];
        $names = [
            'Northwind Books', 'Harbour Museum', 'Blue Fern Hotels', 'City of Elmsworth', 'Kestrel Bank',
            'Greenleaf Grocers', 'Oakridge School', 'Riverside Theatre', 'Atlas Machine Works', 'Lumen Insurance',
            'Seaside Holidays', 'Fieldstone Foundation', 'Copperpot Kitchens', 'Brightwater Trust', 'Mosaic Gallery',
            'Ironbridge Tools', 'Quill & Ink Publishing', 'Summit Sports Club', 'Harvest Food Bank', 'Polar Logistics',
            'Willow Care Homes', 'Beacon Credit Union', 'Tidewater Ferries', 'Orchard Primary Academy',
        ];
        $firstNames = ['Olivia', 'Noah', 'Emma', 'Liam', 'Sophie', 'Lukas', 'Mila', 'Daan', 'Chloé', 'Hugo',
            'Ava', 'Finn', 'Isla', 'Jonas', 'Lea', 'Theo', 'Nora', 'Max', 'Zoe', 'Elias'];
        $surnames = ['Smith', 'Müller', 'de Vries', 'Martin', 'Brown', 'Schmidt', 'Jansen', 'Bernard', 'Taylor',
            'Fischer', 'Bakker', 'Dubois', 'Wilson', 'Weber', 'Visser', 'Moreau'];
        $roles = ['Director', 'Marketing lead', 'Project owner', 'IT manager', 'Communications', 'Finance'];
        $types = Catalogs::singleton()->all('project_type')->filter('Active', true)->toArray();
        $tags = Catalogs::singleton()->all('tags')->toArray();

        foreach ($names as $i => $name) {
            [$city, $country] = $cities[$i % count($cities)];
            $industry = $industries[mt_rand(0, count($industries) - 1)];
            $client = Client::create([
                'Name' => $name,
                'City' => $city,
                'Country' => $country,
                'Website' => 'https://www.' . preg_replace('/[^a-z]+/', '', strtolower($name)) . '.example',
                'IndustryID' => $industry->ID,
                'IsActive' => $i % 7 !== 6,
                'Notes' => $i % 3 === 0 ? 'Prefers calls on Tuesday mornings.' : '',
            ]);
            $client->write();

            for ($c = 0, $n = mt_rand(1, 3); $c < $n; $c++) {
                $first = $firstNames[mt_rand(0, count($firstNames) - 1)];
                $last = $surnames[mt_rand(0, count($surnames) - 1)];
                Contact::create([
                    'FirstName' => $first,
                    'Surname' => $last,
                    'Email' => strtolower(preg_replace('/[^a-z]/i', '', $first . '.' . $last)) . '@'
                        . preg_replace('/[^a-z]+/', '', strtolower($name)) . '.example',
                    'Phone' => '+44 20 7946 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'Role' => $roles[mt_rand(0, count($roles) - 1)],
                    'IsPrimary' => $c === 0,
                    'ClientID' => $client->ID,
                ])->write();
            }

            for ($p = 0, $n = mt_rand(1, 3); $p < $n; $p++) {
                $this->project($client, $types, $tags, $team, $i * 3 + $p);
            }
        }
        $this->log[] = sprintf(
            'Created %d clients, %d contacts, %d projects, %d tasks',
            Client::get()->count(),
            Contact::get()->count(),
            Project::get()->count(),
            Task::get()->count()
        );
    }

    /**
     * @param array<int, CatalogEntry> $types
     * @param array<int, CatalogEntry> $tags
     * @param array<int, Member> $team
     */
    private function project(Client $client, array $types, array $tags, array $team, int $index): void
    {
        $type = $types[mt_rand(0, count($types) - 1)];
        $status = ['lead', 'active', 'active', 'active', 'on_hold', 'done', 'done'][mt_rand(0, 6)];
        $start = strtotime(sprintf('%+d days', mt_rand(-300, 90)));
        $end = strtotime(sprintf('+%d days', mt_rand(20, 200)), $start);
        $topics = [
            'website' => ['New website', 'Website relaunch', 'Online shop', 'Booking system'],
            'app' => ['Member app', 'Visitor guide app', 'Field service app'],
            'branding' => ['New logo and style guide', 'Brand refresh', 'Campaign design'],
            'consulting' => ['Accessibility audit', 'Content strategy', 'Hosting review'],
        ];
        $topicList = $topics[$type->Code] ?? ['Project'];
        $title = $topicList[mt_rand(0, count($topicList) - 1)];
        $manager = $team[mt_rand(0, count($team) - 1)];
        $project = Project::create([
            'Title' => $title,
            'ClientID' => $client->ID,
            'TypeID' => $type->ID,
            'Status' => $status,
            'Priority' => ['low', 'normal', 'normal', 'high'][mt_rand(0, 3)],
            'Budget' => mt_rand(4, 120) * 500,
            'StartDate' => date('Y-m-d', $start),
            'EndDate' => date('Y-m-d', $end),
            'ManagerID' => $manager->ID,
            'Description' => $title . ' for ' . $client->Name . '. Goals, scope and the people involved are '
                . 'described here; the tasks tab lists what is left to do.',
            'InternalNote' => $index % 4 === 0 ? 'Invoice in two parts.' : '',
        ]);
        $project->write();
        foreach (array_rand($tags, 2) as $key) {
            if (mt_rand(0, 1)) {
                $project->Tags()->add($tags[$key]);
            }
        }

        $taskTitles = ['Kick-off meeting', 'Collect content', 'Wireframes', 'Design review', 'Build templates',
            'Set up hosting', 'Accessibility check', 'Training', 'Go live', 'Invoice', 'Write offer',
            'Photo shoot', 'Copy editing', 'Browser testing'];
        shuffle($taskTitles);
        for ($t = 0, $n = mt_rand(3, 8); $t < $n; $t++) {
            $due = strtotime(sprintf('+%d days', mt_rand(0, 60)), $start);
            // Tasks due in the past are mostly done, a few are overdue.
            $taskStatus = match (true) {
                $status === 'done' => 'done',
                $due < time() => mt_rand(0, 5) ? 'done' : 'open',
                default => ['open', 'open', 'in_progress', 'done'][mt_rand(0, 3)],
            };
            Task::create([
                'Title' => $taskTitles[$t],
                'ProjectID' => $project->ID,
                'Status' => $taskStatus,
                'DueDate' => date('Y-m-d', $due),
                'Estimate' => mt_rand(1, 16) / 2,
                'AssigneeID' => mt_rand(0, 4) ? $team[mt_rand(0, count($team) - 1)]->ID : 0,
            ])->write();
        }

        if ($status !== 'lead') {
            ProjectNote::create([
                'ProjectID' => $project->ID,
                'Kind' => 'status',
                'Text' => 'Lead → Active: offer accepted',
                'AuthorID' => $manager->ID,
            ])->write();
        }
        if ($status === 'done' && mt_rand(0, 1)) {
            $project->FeedbackRating = mt_rand(3, 5);
            $project->FeedbackText = 'Great collaboration, thank you!';
            $project->FeedbackAt = date('Y-m-d H:i:s', $end);
            $project->write();
        }
    }
}
