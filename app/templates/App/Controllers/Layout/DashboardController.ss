<div class="fdk-page-header"><h1 class="fdk-page-title">Dashboard</h1></div>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <% loop $Stats %>
    <a href="$Link" class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title">$Label</div>
        <div class="stat-value">$Value</div>
    </a>
    <% end_loop %>
</div>
<%-- [demo:charts] --%>
<div class="grid gap-4 lg:grid-cols-2 mb-6">
    <% include Atwx\SilverstripeFrontdeskKit\Includes\Chart ChartTitle='Projects by status', ChartHeight='280px', ChartConfigJson=$StatusChartJson %>
    <% include Atwx\SilverstripeFrontdeskKit\Includes\Chart ChartTitle='Budget by start month', ChartHeight='280px', ChartConfigJson=$BudgetChartJson %>
</div>
<%-- [/demo:charts] --%>
<%-- [demo:recent] --%>
<% include Atwx\SilverstripeFrontdeskKit\Includes\RecentViews %>
<%-- [/demo:recent] --%>
