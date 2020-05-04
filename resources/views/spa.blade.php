@php
  $config = [
      'appName' => config('app.name'),
      'locale' => $locale = app()->getLocale(),
      'locales' => config('app.locales'),
      'githubAuth' => config('services.github.client_id'),
  ];
@endphp
<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>{{ config('app.name') }}</title>

  <!-- Icons -->
  <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
  <link rel="icon" sizes="192x192" type="image/png" href="{{ asset('media/favicons/favicon-192x192.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">

  <!-- Fonts and Styles -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700">
  <link rel="stylesheet" id="css-main" href="{{ mix('dist/css/dashmix.css') }}">
  <link rel="stylesheet" id="css-theme" href="{{ mix('dist/css/themes/xwork.css') }}">
  <style>
    html {
      min-height: 101vh !important;
    }
  </style>
</head>
<body class="bg-light">
  <div id="app"></div>
  {{-- Global configuration object --}}
  <script>
    window.config = @json($config);
  </script>

  {{-- Load the application scripts --}}

  <script src="{{ mix('dist/js/dashmix.app.js') }}"></script>
  <script src="{{ mix('dist/js/app.js') }}"></script>
  <script src="{{ mix('dist/js/vendor.js') }}"></script>

  <!-- Page JS Helpers (Table Tools helpers) -->
  <script>
    jQuery(function(){
      //console.log(Dashmix);
      //Dashmix.helpers(['table-tools-checkable', 'table-tools-sections']);
    });
  </script>

</body>
</html>
