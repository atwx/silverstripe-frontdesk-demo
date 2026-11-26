<!DOCTYPE html>
<html lang="de" data-theme="light">
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
<main class="fdk-main">
    $Layout
</main>
<% include Footer %>
<script src="$resourceURL('atwx/silverstripe-frontdesk-kit:client/dist/frontdesk.js')" defer></script>
<script type="module" src="$Vite('app/client/src/js/main.js')"></script>
</body>
</html>
