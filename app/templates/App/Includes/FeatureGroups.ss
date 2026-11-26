<%-- All features by group, each with its summary. --%>
<% loop $FeatureGroups %>
<section class="mb-8">
    <h2 class="text-lg font-semibold mb-3">$Title</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <% loop $Features %>
        <a href="$Link" class="card bg-base-100 border border-base-300 hover:border-primary transition-colors">
            <div class="card-body p-4">
                <h3 class="font-semibold">$Title</h3>
                <p class="text-sm text-base-content/70">$Summary</p>
            </div>
        </a>
        <% end_loop %>
    </div>
</section>
<% end_loop %>
