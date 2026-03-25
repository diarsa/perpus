<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ (isset($title) && $title ? $title . ' - ' : '') . config('app.name') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<!-- jQuery & Select2 (Global) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .select2-container { z-index: 99999 !important; width: 100% !important; }
    .select2-dropdown { z-index: 100000 !important; }
    .select2-container--default .select2-selection--single { height: 38px !important; border-radius: 8px !important; border-color: #e2e8f0 !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; padding-left: 12px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px !important; }
</style>
