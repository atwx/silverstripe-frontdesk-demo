<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Start page after login: key figures, two charts and the records the member
 * viewed last.
 *
 */
class DashboardController extends BaseController
{
    protected function init(): void
    {
        parent::init();
        if (!Permission::check('FDK_PROJECTS_VIEW')) {
            Security::permissionFailure($this);
        }
    }

    public function index(HTTPRequest $request)
    {
        return ['Title' => 'Dashboard'];
    }

    /**
     * @return ArrayList<ArrayData>
     */
    public function Stats(): ArrayList
    {
        $me = (int) Security::getCurrentUser()?->ID;
        $open = Task::get()->exclude('Status', 'done');
        $stats = [
            ['Active clients', Client::get()->filter('IsActive', true)->count(), 'clients'],
            ['Active projects', Project::get()->filter('Status', 'active')->count(), 'projects?view=active'],
            ['My open tasks', $open->filter('AssigneeID', $me)->count(), 'tasks?view=mine'],
            ['Overdue tasks', $open->filter('DueDate:LessThan', date('Y-m-d'))->count(), 'tasks?view=overdue'],
        ];
        return ArrayList::create(array_map(
            fn (array $stat) => ArrayData::create(['Label' => $stat[0], 'Value' => $stat[1], 'Link' => $stat[2]]),
            $stats
        ));
    }

    // [demo:charts]
    /**
     * Projects per status (doughnut).
     */
    public function StatusChartJson(): string
    {
        $labels = [];
        $counts = [];
        foreach (Project::STATUSES as $status => $label) {
            $labels[] = $label;
            $counts[] = Project::get()->filter('Status', $status)->count();
        }
        return (string) json_encode([
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [['data' => $counts, 'backgroundColor' => ['#94a3b8', '#22c55e', '#f59e0b', '#6366f1']]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false],
        ]);
    }

    /**
     * Budget of the projects starting per month, this year (bars).
     */
    public function BudgetChartJson(): string
    {
        $year = (int) date('Y');
        $labels = [];
        $sums = [];
        for ($month = 1; $month <= 12; $month++) {
            $from = sprintf('%d-%02d-01', $year, $month);
            $labels[] = date('M', (int) strtotime($from));
            $sums[] = (float) Project::get()->filter([
                'StartDate:GreaterThanOrEqual' => $from,
                'StartDate:LessThanOrEqual' => date('Y-m-t', (int) strtotime($from)),
            ])->sum('Budget');
        }
        return (string) json_encode([
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [['label' => 'Budget (€)', 'data' => $sums, 'backgroundColor' => '#6366f1']],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false],
        ]);
    }
    // [/demo:charts]
}
