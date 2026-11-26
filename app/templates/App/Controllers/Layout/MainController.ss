<section class="max-w-5xl mx-auto">
    <div class="py-6">
        <h1 class="text-3xl font-bold mb-3">silverstripe-frontdesk-kit</h1>
        <p class="text-lg text-base-content/80 max-w-3xl">
            A back office for Silverstripe without the CMS: lists with filters, views and exports, record pages,
            forms with access levels, bulk actions and more – each built from a few lines of PHP in a controller.
            This demo shows every feature live, next to the code that builds it.
        </p>
        <div class="flex flex-wrap gap-2 mt-4">
            <% if $CurrentMember %>
            <a href="dashboard" class="btn btn-primary">Open the demo</a>
            <% else %>
            <a href="Security/login?BackURL=dashboard" class="btn btn-primary">Log in to the demo</a>
            <% end_if %>
            <a href="https://github.com/atwx/silverstripe-frontdesk-kit" class="btn btn-ghost">Kit on GitHub</a>
        </div>
    </div>
    <% if not $CurrentMember %><% include App\Includes\DemoLogin %><% end_if %>
    <div class="mt-8">
        <% include App\Includes\FeatureGroups %>
    </div>
</section>
