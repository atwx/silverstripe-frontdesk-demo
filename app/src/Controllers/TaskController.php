<?php

namespace App\Controllers;

use App\Models\Task;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskController;
use Atwx\SilverstripeFrontdeskKit\Filter\FilterCollection;
use Atwx\SilverstripeFrontdeskKit\Filter\SelectFilter;
use Atwx\SilverstripeFrontdeskKit\Filter\TextFilter;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use Atwx\SilverstripeFrontdeskKit\Table\ListView;
use Atwx\SilverstripeFrontdeskKit\Table\RowAction;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * All open tasks across projects, grouped by project, with a row action
 * that marks a task as done in place; the navigation shows how many are
 * mine and how many have no assignee.
 *
 */
class TaskController extends FrontdeskController
{
    private static string $managed_model = Task::class;

    private static string $url_segment = 'tasks';

    private static string $title = 'Tasks';

    private static string $permission_code = 'FDK_PROJECTS';

    /**
     * Tasks are added in their project.
     */
    private static bool $allow_add = false;

    private static string $default_list_view = 'mine';

    private static array $allowed_actions = [
        'done' => '->canEdit',
    ];

    // [demo:navigation]
    /**
     * Badge of the navigation: my open tasks.
     */
    public static function countMine(): int
    {
        $me = (int) Security::getCurrentUser()?->ID;
        return $me ? Task::get()->filter(['AssigneeID' => $me])->exclude('Status', 'done')->count() : 0;
    }

    /**
     * Second badge: open tasks without an assignee.
     */
    public static function countUnassigned(): int
    {
        return Security::getCurrentUser()
            ? Task::get()->filter(['AssigneeID' => 0])->exclude('Status', 'done')->count()
            : 0;
    }
    // [/demo:navigation]

    protected function getBaseList(): DataList
    {
        return parent::getBaseList()->exclude('Status', 'done');
    }

    // [demo:grouped]
    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Title', 'Task')->end()
            ->make('StatusLabel', 'Status')->type('badge')->end()
            ->make('DueDate', 'Due')
                ->format(fn ($value) => $value ? date('d M Y', (int) strtotime((string) $value)) : '')
                ->sortable()->end()
            ->make('Assignee.Name', 'Assignee')->end()
            ->groupBy('Project.Title', 'Without project');
    }
    // [/demo:grouped]

    protected function defineFilters(): FilterCollection
    {
        return FilterCollection::create()
            ->add(TextFilter::create('Query', 'Search')
                ->apply(fn (DataList $list, $value) => $list->filter('Title:PartialMatch', $value)))
            ->add(SelectFilter::create('Status', 'Status')
                ->options(['open' => 'Open', 'in_progress' => 'In progress'])
                ->apply(fn (DataList $list, $value) => $list->filter('Status', $value)));
    }

    protected function defineListViews(): array
    {
        $me = (int) Security::getCurrentUser()?->ID;
        return [
            ListView::create('mine', 'Mine')
                ->query(fn (DataList $list) => $list->filter('AssigneeID', $me)->sort('Project.Title, DueDate')),
            ListView::create('unassigned', 'Unassigned')
                ->query(fn (DataList $list) => $list->filter('AssigneeID', 0)->sort('Project.Title, DueDate')),
            ListView::create('overdue', 'Overdue')
                ->query(fn (DataList $list) => $list->filter('DueDate:LessThan', date('Y-m-d'))
                    ->sort('Project.Title, DueDate')),
        ];
    }

    // [demo:rowactions]
    protected function defineRowActions(DataObject $record): array
    {
        /** @var Task $record */
        return [
            RowAction::link('Open project', ProjectController::singleton()->Link('view/' . $record->ProjectID))
                ->withIcon('external-link')
                ->direct(),
            RowAction::htmx('Mark as done', $this->Link('done/' . $record->ID))
                ->withIcon('check')
                ->direct()
                ->enabled(fn () => $this->canEdit()),
        ];
    }

    /**
     * GET: confirmation in the modal; POST (with security token): marks the
     * task as done and goes back to the list.
     */
    public function done(HTTPRequest $request): HTTPResponse
    {
        /** @var Task|null $task */
        $task = $this->findRecord((int) $request->param('ID'), 'canEdit');
        if (!$task) {
            return $this->httpError(404);
        }
        if (!$request->isPOST()) {
            return $this->renderPartial('App\\Includes\\ConfirmDone', [
                'Task' => $task,
                'Action' => $this->Link('done/' . $task->ID),
                'Token' => SecurityToken::inst()->getValue(),
            ]);
        }
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }
        $task->Status = 'done';
        $task->write();
        $this->flash('"' . $task->Title . '" is done.');
        return $this->redirect($this->Link() . '?' . http_build_query(['view' => $request->getVar('view') ?: 'mine']));
    }
    // [/demo:rowactions]
}
