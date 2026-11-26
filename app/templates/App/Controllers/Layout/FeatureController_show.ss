<% with $Feature %>
<article class="demo-feature max-w-5xl mx-auto">
    <nav class="text-sm breadcrumbs mb-2" aria-label="Breadcrumb">
        <ul><li><a href="features">Features</a></li><% if $Group %><li>$Group</li><% end_if %></ul>
    </nav>
    <h1 class="fdk-page-title mb-3">$Title</h1>
    <p class="text-lg text-base-content/80 mb-4">$Summary</p>
    <% if $Points %>
    <ul class="list-disc ps-6 mb-4 space-y-1">
        <% loop $Points %><li>$Text</li><% end_loop %>
    </ul>
    <% end_if %>
    <div class="flex flex-wrap gap-2 mb-6">
        <% if $Top.LoggedIn %>
        <a href="$LiveLink" class="btn btn-primary"><i data-lucide="external-link" class="size-4"></i> Try it live</a>
        <% else %>
        <a href="Security/login?BackURL=$LiveLink.URLATT" class="btn btn-primary">Log in and try it live</a>
        <% end_if %>
        <% if $DocsLink %><a href="$DocsLink" class="btn btn-ghost"><i data-lucide="file-text" class="size-4"></i> Documentation</a><% end_if %>
    </div>
    <% if not $Top.LoggedIn %><% include App\Includes\DemoLogin %><% end_if %>

    <h2 class="text-xl font-semibold mt-8 mb-3">The code</h2>
    <% loop $Snippets %>
    <figure class="demo-snippet mb-6">
        <figcaption class="flex flex-wrap items-baseline justify-between gap-2 mb-1">
            <span><code class="text-sm">$File</code><% if $Caption %> <span class="text-base-content/70">– $Caption</span><% end_if %></span>
            <a href="$GitHubLink" class="link text-sm">whole file</a>
        </figcaption>
        <pre class="demo-code"><code class="language-$Language">$Code</code></pre>
    </figure>
    <% end_loop %>
</article>
<% end_with %>
<nav class="max-w-5xl mx-auto flex justify-between gap-4 mt-8" aria-label="More features">
    <% if $Previous %><a href="$Previous.Link" class="link">← $Previous.Title</a><% else %><span></span><% end_if %>
    <% if $Next %><a href="$Next.Link" class="link">$Next.Title →</a><% end_if %>
</nav>
