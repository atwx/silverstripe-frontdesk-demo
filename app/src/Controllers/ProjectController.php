<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectNote;
use Atwx\SilverstripeFrontdeskKit\Catalog\CatalogFilter;
use Atwx\SilverstripeFrontdeskKit\Catalog\Catalogs;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskController;
use Atwx\SilverstripeFrontdeskKit\Filter\DateRangeFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\FilterCollection;
use Atwx\SilverstripeFrontdeskKit\Filter\MultiSelectFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\SelectFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\TextFilter;
use Atwx\SilverstripeFrontdeskKit\Forms\FieldLayout;
use Atwx\SilverstripeFrontdeskKit\Table\ActionException;
use Atwx\SilverstripeFrontdeskKit\Table\BulkAction;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use Atwx\SilverstripeFrontdeskKit\Table\Export;
use Atwx\SilverstripeFrontdeskKit\Table\ListView;
use Atwx\SilverstripeFrontdeskKit\Table\RecordAction;
use Atwx\SilverstripeFrontdeskKit\Table\TimelineEntry;
use Atwx\SilverstripeFrontdeskKit\View\RecordCard;
use Atwx\SilverstripeFrontdeskKit\View\RecordPage;
use Atwx\SilverstripeFrontdeskKit\View\RecordPages;
use Atwx\SilverstripeFrontdeskKit\View\RecordSwitcher;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Projects: the list shows columns, filters, views, exports and bulk
 * actions; a project opens in the layout "cards and tabs" with tasks,
 * notes, record actions and a timeline.
 *
 */
class ProjectController extends FrontdeskController
{
    private static string $managed_model = Project::class;

    private static string $url_segment = 'projects';

    private static string $title = 'Projects';

    // [demo:recent]
    /**
     * Note every opened project per member; "Recently viewed" becomes a list view.
     */
    private static $recent_views = 20;
    // [/demo:recent]

    // [demo:subcontrollers]
    private static array $sub_controllers = [
        TaskSubController::class,
    ];

    private static array $allowed_actions = [
        'tasks',
    ];

    public function tasks(HTTPRequest $request): TaskSubController
    {
        return TaskSubController::create()->setParentContext($this, (int) $request->param('ID'));
    }
    // [/demo:subcontrollers]

    // [demo:columns]
    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Title', 'Project')->link('view/{ID}')->sortable()->end()
            ->make('Client.Name', 'Client')->sortable('Client.Name')->end()
            ->make('StatusLabel', 'Status')->type('badge')->sortable('Status')->end()
            ->make('Type.Title', 'Type')->end()
            ->make('Tags', 'Tags')->end()
            ->make('Budget', 'Budget')
                ->format(fn ($value) => number_format((float) $value, 0, '.', ',') . ' €')
                ->sortable()->width(12)->end()
            ->make('StartDate', 'Start')
                ->format(fn ($value) => $value ? date('d M Y', (int) strtotime((string) $value)) : '')
                ->sortable()->end()
            ->make('Manager.Name', 'Manager')->sortable('Manager.Surname')->end()
            ->make('Progress', 'Progress')
                ->format(fn ($value) => $value === null ? '–' : $value . ' %')
                ->optional()->end()
            ->make('InternalNote', 'Internal note')->exportOnly()->end()
            ->lastViewed('Last viewed')->optional()->end();
    }
    // [/demo:columns]

    // [demo:filters]
    protected function defineFilters(): FilterCollection
    {
        return FilterCollection::create()
            ->add(TextFilter::create('Query', 'Search')
                ->apply(fn (DataList $list, $value) => $list->filterAny([
                    'Title:PartialMatch' => $value,
                    'Client.Name:PartialMatch' => $value,
                    'Description:PartialMatch' => $value,
                ])))
            ->add(SelectFilter::create('Status', 'Status')
                ->options(Project::STATUSES)
                ->apply(fn (DataList $list, $value) => $list->filter('Status', $value)))
            ->add(CatalogFilter::create('Type', 'Type', 'project_type'))
            ->add(MultiSelectFilter::create('Tags', 'Tags')
                ->options(fn () => Catalogs::singleton()->options(Catalogs::singleton()->all('tags')))
                ->field('Tags.ID')
                ->choosableMatch())
            ->add(SelectFilter::create('Manager', 'Manager')
                ->options(fn () => $this->managerOptions())
                ->apply(fn (DataList $list, $value) => $list->filter('ManagerID', (int) $value)))
            ->add(DateRangeFilter::create('Start', 'Starts between')
                ->apply(function (DataList $list, array $range) {
                    if ($range['from']) {
                        $list = $list->filter('StartDate:GreaterThanOrEqual', $range['from']);
                    }
                    if ($range['to']) {
                        $list = $list->filter('StartDate:LessThanOrEqual', $range['to']);
                    }
                    return $list;
                }));
    }
    // [/demo:filters]

    // [demo:listviews]
    protected function defineListViews(): array
    {
        $me = (int) Security::getCurrentUser()?->ID;
        return [
            ListView::create('active', 'Active')
                ->filters(['Status' => 'active']),
            ListView::create('mine', 'My projects')
                ->query(fn (DataList $list) => $list->filter('ManagerID', $me)),
            ListView::create('ending', 'Ending within 30 days')
                ->query(fn (DataList $list) => $list->filter([
                    'Status' => 'active',
                    'EndDate:GreaterThanOrEqual' => date('Y-m-d'),
                    'EndDate:LessThanOrEqual' => date('Y-m-d', strtotime('+30 days')),
                ]))
                ->sort('EndDate ASC')
                ->columns(['Title', 'Client.Name', 'Manager.Name', 'Progress']),
        ];
    }
    // [/demo:listviews]

    // [demo:export]
    /**
     * Besides "Export" (the list's columns): a budget sheet with its own
     * columns and file name.
     */
    protected function defineExports(): array
    {
        return [
            Export::create('budget', 'Budget sheet')
                ->fileName('Budgets ' . date('Y'))
                ->columns(fn () => ColumnCollection::create()
                    ->make('Client.Name', 'Client')->width(30)->end()
                    ->make('Title', 'Project')->width(40)->end()
                    ->make('StatusLabel', 'Status')->end()
                    ->make('Budget', 'Budget (€)')->width(14)->end())
                ->query(fn (DataList $list) => $list->exclude('Status', 'lead')->sort('Client.Name')),
        ];
    }
    // [/demo:export]

    // [demo:bulk]
    protected function defineBulkActions(): array
    {
        return [
            BulkAction::create('status', 'Set status to', function (DataList $projects, ?string $status) {
                foreach ($projects as $project) {
                    $project->Status = $status;
                    $project->write();
                }
                return $projects->count() . ' projects changed.';
            })->options(Project::STATUSES),

            BulkAction::create('assign', 'Assign manager …', function (DataList $projects, array $data) {
                foreach ($projects as $project) {
                    $project->ManagerID = (int) $data['ManagerID'];
                    $project->Priority = (string) $data['Priority'];
                    $project->write();
                }
                return $projects->count() . ' projects assigned.';
            })
                ->fields(fn () => FieldList::create(
                    DropdownField::create('ManagerID', 'Manager', $this->managerOptions())->setHasEmptyDefault(true),
                    DropdownField::create('Priority', 'Priority', Project::PRIORITIES)->setValue('normal')
                ), ['ManagerID'])
                ->submitLabel('Assign'),

            BulkAction::create('delete', 'Delete', function (DataList $projects) {
                $count = $projects->count();
                foreach ($projects as $project) {
                    $project->delete();
                }
                return $count . ' projects deleted.';
            })->confirm('Delete {count} projects?'),
        ];
    }
    // [/demo:bulk]

    // [demo:fieldlayout]
    /**
     * Groups of the form and the view. A project that is done keeps its
     * budget read-only; the feedback comes from the client and is read-only.
     */
    protected function defineFieldLayout(?DataObject $record): ?FieldLayout
    {
        $layout = FieldLayout::create()
            ->group('Project', ['Title', 'ClientID', 'TypeID', 'Tags', 'Status', 'Priority'], true, 'basics')
            ->group('Schedule and budget', ['StartDate', 'EndDate', 'Budget', 'ManagerID'], true, 'schedule')
            ->group('Description', ['Description'], true, 'description')
            ->group('Internal', ['InternalNote'], false, 'internal')
            ->group('Client feedback', ['FeedbackRating', 'FeedbackText', 'FeedbackAt'], false, 'feedback')
            ->setLevels([
                'FeedbackRating' => FieldLayout::READ,
                'FeedbackText' => FieldLayout::READ,
                'FeedbackAt' => FieldLayout::READ,
            ]);
        if ($record?->Status === 'done') {
            $layout->setLevel('Budget', FieldLayout::READ);
        }
        return $layout;
    }
    // [/demo:fieldlayout]

    // [demo:cards]
    protected function defineRecordPages(DataObject $record): ?RecordPages
    {
        /** @var Project $record */
        return RecordPages::create()
            ->layout('tabs')
            ->switcher($this->clientProjects($record))
            ->page(RecordPage::create('tasks', 'Tasks')->sublist(TaskSubController::class, ''))
            ->page(RecordPage::create('description', 'Description')->groups(['description']))
            ->page(RecordPage::create('history', 'History')->timeline())
            ->card(RecordCard::create('client', 'Client')
                ->related(fn (Project $project) => $project->Client(), ClientController::class))
            ->groupCards(null, ['description'])
            ->card(RecordCard::create('share', 'Feedback link')
                ->open(false)
                ->summary(fn (Project $project) => $project->FeedbackAt ? 'Feedback received' : 'No feedback yet')
                ->content(fn (Project $project) => $this->feedbackLinkHtml($project)));
    }

    /**
     * Right of the title: status and priority.
     */
    protected function defineHeaderStatus(DataObject $record): ?ArrayList
    {
        /** @var Project $record */
        return ArrayList::create([
            ArrayData::create([
                'Title' => 'Status',
                'Value' => $record->getStatusLabel(),
                'Color' => ['active' => 'success', 'on_hold' => 'warning', 'done' => 'neutral'][$record->Status] ?? '',
            ]),
            ArrayData::create(['Title' => 'Priority', 'Value' => $record->getPriorityLabel()]),
        ]);
    }

    /**
     * The other projects of the same client, with a link to add one.
     */
    private function clientProjects(Project $project): RecordSwitcher
    {
        $segment = (string) $this->getRequest()->param('OtherID');
        $switcher = RecordSwitcher::create()->withAddLink($this->Link('add'), '+ Project');
        $client = $project->Client();
        $others = $client->exists() ? $client->Projects()->sort('StartDate', 'DESC') : [$project];
        foreach ($others as $other) {
            $switcher->option(
                (string) $other->Title,
                $this->Link('view/' . $other->ID . ($segment !== '' ? '/' . $segment : '')),
                $other->ID === $project->ID
            );
        }
        return $switcher;
    }
    // [/demo:cards]

    // [demo:recordactions]
    protected function defineRecordActions(DataObject $record): array
    {
        if (!$this->canEdit()) {
            return [];
        }
        /** @var Project $record */
        $statuses = Project::STATUSES;
        unset($statuses[$record->Status]);
        return [
            RecordAction::create('status', 'Change status')
                ->options($statuses)
                ->withComment('Comment')
                ->handle(function (Project $project, ?string $status, string $comment) {
                    if ($status === 'done' && $project->Tasks()->exclude('Status', 'done')->exists()) {
                        throw new ActionException('Finish the open tasks first.');
                    }
                    $old = $project->getStatusLabel();
                    $project->Status = (string) $status;
                    $project->write();
                    $this->note($project, 'status', $old . ' → ' . $project->getStatusLabel()
                        . ($comment !== '' ? ': ' . $comment : ''));
                    return 'Status changed to ' . $project->getStatusLabel() . '.';
                }),
            RecordAction::create('note', 'Add note')
                ->withComment('Note', true)
                ->input('followup', 'Follow up on', 'date')
                ->handle(function (Project $project, ?string $option, string $comment, array $values) {
                    $date = (string) ($values['followup'] ?? '');
                    $this->note($project, 'note', $comment . ($date !== '' ? ' (follow up on ' . $date . ')' : ''));
                    return 'Note added.';
                }),
        ];
    }

    private function note(Project $project, string $kind, string $text): void
    {
        ProjectNote::create([
            'ProjectID' => $project->ID,
            'Kind' => $kind,
            'Text' => $text,
            'AuthorID' => (int) Security::getCurrentUser()?->ID,
        ])->write();
    }
    // [/demo:recordactions]

    // [demo:timeline]
    protected function defineTimeline(DataObject $record): ?ArrayList
    {
        /** @var Project $record */
        $entries = ArrayList::create([
            TimelineEntry::create((string) $record->Created, 'Project created', '', '', 'created'),
        ]);
        $titles = ['status' => 'Status changed', 'note' => 'Note', 'feedback' => 'Client feedback'];
        foreach ($record->Notes() as $note) {
            $entries->push(TimelineEntry::create(
                (string) $note->Created,
                $titles[$note->Kind] ?? 'Note',
                (string) $note->Text,
                $note->Author()->exists() ? (string) $note->Author()->getName() : '',
                (string) $note->Kind
            ));
        }
        return $entries;
    }

    protected function timelineTitle(): string
    {
        return 'History';
    }
    // [/demo:timeline]

    protected function formFields(FieldList $fields): FieldList
    {
        $fields->replaceField('Status', DropdownField::create('Status', 'Status', Project::STATUSES));
        $fields->replaceField('Priority', DropdownField::create('Priority', 'Priority', Project::PRIORITIES));
        $fields->dataFieldByName('TypeID')?->setTitle('Type');
        return $fields;
    }

    /**
     * Labels instead of the stored values of status and priority; no rating
     * before the client gave one.
     *
     * @return array<string, ArrayData>
     */
    protected function viewFieldRows(DataObject $record): array
    {
        /** @var Project $record */
        $rows = parent::viewFieldRows($record);
        $labels = ['Status' => $record->getStatusLabel(), 'Priority' => $record->getPriorityLabel()];
        foreach ($labels as $name => $label) {
            if (isset($rows[$name])) {
                $rows[$name] = ArrayData::create(['Label' => $rows[$name]->Label, 'Value' => $label, 'Type' => 'text']);
            }
        }
        if (!$record->FeedbackRating) {
            unset($rows['FeedbackRating']);
        }
        return $rows;
    }

    /**
     * Members of the team group (managers and assignees).
     *
     * @return array<int, string>
     */
    public function managerOptions(): array
    {
        $options = [];
        foreach (Member::get()->filter('Groups.Code', 'team')->sort('Surname') as $member) {
            $options[(int) $member->ID] = (string) $member->getName();
        }
        return $options;
    }

    private function feedbackLinkHtml(Project $project): string
    {
        $link = FeedbackController::linkFor($project);
        return '<p class="mb-2">The client gives feedback through this link, without an account:</p>'
            . '<p><a class="link link-primary break-all" href="' . htmlspecialchars($link) . '">'
            . htmlspecialchars($link) . '</a></p>';
    }
}
