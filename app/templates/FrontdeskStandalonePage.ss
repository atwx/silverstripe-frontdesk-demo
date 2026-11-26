<!DOCTYPE html>
<%-- Page of StandaloneRecordController (feedback link) with the project's stylesheet. --%>
<html lang="$ContentLocale" data-theme="light">
<head>
    <% base_tag %>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>$Title</title>
    $ViteClient.RAW
    <link rel="stylesheet" href="$Vite('app/client/src/css/tailwind.css')">
</head>
<body class="fdk-app">
<main class="fdk-main">
    $Layout
</main>
<script src="$resourceURL('atwx/silverstripe-frontdesk-kit:client/dist/frontdesk.js')" defer></script>
</body>
</html>
