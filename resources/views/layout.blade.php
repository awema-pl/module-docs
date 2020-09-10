<!doctype html>
<html lang="en">
<head>
    <title>Docs</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <style>
        .version-selector {
            padding: .25rem 1.5rem;
            color: rgba(0, 0, 0, .65);
        }

        .bd-toc-link {
            display: block;
            padding: .25rem 1.5rem;
            font-weight: 600;
            color: rgba(0, 0, 0, .65);
        }

        .bd-toc-item.active > .bd-toc-link {
            color: rgba(0, 0, 0, .85);
        }

        .bd-sidenav {
            padding-left: 15px;
            margin-bottom: 0;
            list-style: none;
        }

        .bd-sidenav > li > a {
            display: block;
            padding: .25rem 1.5rem;
            font-size: 90%;
            color: rgba(0, 0, 0, .65);
        }

        .bd-sidenav > li.active > a, .bd-sidenav > li > a:hover {
            color: rgba(0, 0, 0, .85);
            text-decoration: none;
            background-color: transparent;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-xl-nowrap">
        <div class="col-12 col-md-3 col-xl-2 bd-sidebar">
            @include('docs::chunks.version-selector')
            @include('docs::chunks.sidebar')
        </div>
        <div class="col-12 col-md-9 col-xl-8 bd-content">
            @if(!empty($h1))
                <h1>{{$h1}}</h1>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
