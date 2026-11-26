<?php

namespace App\Controllers;

use App\Models\Task;
use Atwx\SilverstripeFrontdeskKit\Controller\FrontdeskSubController;
use Atwx\SilverstripeFrontdeskKit\Table\ColumnCollection;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;

/**
 * Tasks of a project, as a sub-list of the project (tab "Tasks").
 *
 */
// [demo:subcontrollers]
class TaskSubController extends FrontdeskSubController
{
    private static string $managed_model = Task::class;

    private static string $url_segment = 'tasks';

    private static string $title = 'Tasks';

    private static string $parent_field = 'ProjectID';

    /**
     * "Edit several": only filled fields are applied to the chosen tasks.
     */
    private static array $bulk_edit_fields = ['Status', 'AssigneeID', 'DueDate'];

    protected function getBaseQuery(): DataList
    {
        return Task::get()->filter('ProjectID', $this->getParentID());
    }

    protected function defineColumns(): ColumnCollection
    {
        return ColumnCollection::create()
            ->make('Title', 'Task')->sortable()->end()
            ->make('StatusLabel', 'Status')->type('badge')->sortable('Status')->end()
            ->make('DueDate', 'Due')
                ->format(fn ($value) => $value ? date('d M Y', (int) strtotime((string) $value)) : '')
                ->sortable()->end()
            ->make('Assignee.Name', 'Assignee')->sortable('Assignee.Surname')->end()
            ->make('Estimate', 'Estimate (h)')->sortable()->end();
    }
    // [/demo:subcontrollers]

    protected function formFields(FieldList $fields): FieldList
    {
        $fields = parent::formFields($fields);
        $fields->replaceField('AssigneeID', DropdownField::create(
            'AssigneeID',
            'Assignee',
            ProjectController::singleton()->managerOptions()
        )->setEmptyString('–'));
        $fields->replaceField('Status', DropdownField::create('Status', 'Status', Task::STATUSES));
        return $fields;
    }
// [demo:subcontrollers]
}
// [/demo:subcontrollers]
