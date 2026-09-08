<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow-sm mx-auto" style="max-width: 480px;">
                <div class="card-body text-center">
                    <h1 class="card-title mb-3">MVP</h1>
                    <p class="text-muted mb-4">Entorno funcionando correctamente.</p>
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Laravel
                            <span class="badge bg-success">OK</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PostgreSQL
                            @if ($dbStatus)
                                <span class="badge bg-success">OK</span>
                            @else
                                <span class="badge bg-danger">ERROR</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Bootstrap
                            <span class="badge bg-success">OK</span>
                        </li>
                    </ul>
                    @if (!$dbStatus)
                        <p class="text-danger small mt-3 mb-0">{{ $dbError }}</p>
                    @endif
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
