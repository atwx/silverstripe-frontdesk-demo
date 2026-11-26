<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <% base_tag %>
    $MetaTags(false)
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <title>$Title - $SiteConfig.Title</title>
    $ViteClient.RAW
    <link rel="stylesheet" href="$Vite('app/client/src/scss/main.scss')">
    <%-- Single Tailwind build; imports the frontdesk-kit preset (see tailwind.css). --%>
    <link rel="stylesheet" href="$Vite('app/client/src/css/tailwind.css')">
</head>
<body class="fdk-app">
<% include Header %>
<% if $DemoFeaturesHere %>
<nav class="demo-feature-bar" aria-label="How this page is built">
    <span class="demo-feature-bar-label"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg> How is this built?</span>
    <% loop $DemoFeaturesHere %><a href="$Link" class="demo-feature-chip">$Title</a><% end_loop %>
</nav>
<% end_if %>
<main class="fdk-main">
    $Layout
</main>
<% include Footer %>
<script src="$resourceURL('atwx/silverstripe-frontdesk-kit:client/dist/frontdesk.js')" defer></script>
<script type="module" src="$Vite('app/client/src/js/main.js')"></script>
</body>
</html>
