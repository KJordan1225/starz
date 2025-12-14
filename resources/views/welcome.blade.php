<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>StarCity Starz</title>

        {{-- Google-style web font (optional) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Bootstrap 5 CSS --}}
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
            crossorigin="anonymous"
        >

        {{-- Optional: your compiled assets if you still use Vite --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            body {
                font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="d-flex min-vh-100 align-items-center justify-content-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header text-center">
                                <h1 class="h5 mb-0">StarCity Starz</h1>
                            </div>

                            <div class="card-body text-center">
                                @if (Route::has('login'))
                                    @auth
                                        <p class="mb-3">
                                            You are already logged in.
                                        </p>
                                        <a
                                            href="{{ url('/dashboard') }}"
                                            class="btn btn-primary w-100 mb-2"
                                        >
                                            Go to Dashboard
                                        </a>
                                    @else
                                        <p class="mb-4">
                                            Please log in or register to continue.
                                        </p>

                                        <a
                                            href="{{ route('login') }}"
                                            class="btn btn-primary w-100 mb-2"
                                        >
                                            Log in
                                        </a>

                                        @if (Route::has('register'))
                                            <a
                                                href="{{ route('register') }}"
                                                class="btn btn-outline-primary w-100"
                                            >
                                                Register
                                            </a>
                                        @endif
                                        
                                        <a href="{{ route('landlord.microsite.create') }}"
	                                        class="btn btn-outline-primary w-100"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Click to establish micro-site!">Establish Your Micro-site</a>         
                                    @endauth
                                @endif
                            </div>

                            <div class="card-footer text-center small text-muted">
                                Laravel v{{ Illuminate\Foundation\Application::VERSION }}
                                (PHP v{{ PHP_VERSION }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bootstrap 5 JS (optional, for dropdowns/modals if you add later) --}}
        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"
        ></script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        </script>
    </body>
</html>
