<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page-bg">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-12 col-sm-8 col-md-6 col-lg-4">
        
        <div class="card login-card">
            <div class="card-body p-4 p-sm-5">
                
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Sybella Logo" class="login-logo img-fluid">
                    <h4 class="mt-3 fw-bold text-dark">Employee Login</h4>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger text-center alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Employee Code</label>
                        <div class="input-group input-group-custom">
                            <span class="icon-box"><i class="bi bi-person-fill"></i></span>
                            <input type="text" name="employee_code" class="form-control custom-input" placeholder="Enter Employee Code">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Password</label>
                        <div class="input-group input-group-custom">
                            <span class="icon-box"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" class="form-control custom-input" placeholder="Enter Password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login fw-bold">Login</button>
                </form>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="{{ asset('js/script.js') }}"></script>
</body>
</html>