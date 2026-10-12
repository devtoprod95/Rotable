<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if ($assetsPublished)
        <link rel="shortcut icon" href="{{ asset(mix('img/log-viewer-32.png', config('log-viewer.assets_path'))) }}">
    @else
        {!! \Opcodes\LogViewer\Facades\LogViewer::favicon() !!}
    @endif

    <title>Log Viewer{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    @if ($assetsPublished)
        <link href="{{ asset(mix('app.css', config('log-viewer.assets_path'))) }}" rel="stylesheet" onerror="alert('app.css failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')">
    @else
        {!! \Opcodes\LogViewer\Facades\LogViewer::css() !!}
    @endif
</head>

<body class="h-full px-3 lg:px-5 bg-gray-100 dark:bg-gray-900">
<div id="log-viewer" class="flex h-full max-h-screen max-w-full">
    <router-view></router-view>
</div>

<!-- Global LogViewer Object -->
<script>
    window.LogViewer = @json($logViewerScriptVariables);

    // Keep all file types selected by default on each page load.
    try {
        localStorage.removeItem('selectedFileTypes');
    } catch (e) {
        // Ignore storage access failures.
    }

    // Add additional headers for LogViewer requests like so:
    // window.LogViewer.headers['Authorization'] = 'Bearer xxxxxxx';
</script>
@if ($assetsPublished)
    <script src="{{ asset(mix('app.js', config('log-viewer.assets_path'))) }}" onerror="alert('app.js failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')"></script>
@else
    {!! \Opcodes\LogViewer\Facades\LogViewer::js() !!}
@endif
<script>
    (function () {
        function hideFileTypeSelector() {
            var labels = document.querySelectorAll('#log-viewer label');
            labels.forEach(function (label) {
                if ((label.textContent || '').trim() !== 'Selected file types') {
                    return;
                }

                var container = label.closest('div.mb-8.mt-6') || label.closest('div');
                if (container) {
                    container.style.display = 'none';
                }
            });
        }

        var observer = new MutationObserver(hideFileTypeSelector);
        var root = document.getElementById('log-viewer');

        if (root) {
            observer.observe(root, { childList: true, subtree: true });
            hideFileTypeSelector();
        }
    })();
</script>
</body>
</html>
