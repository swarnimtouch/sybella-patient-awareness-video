<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sybella -Employee Login</title>

    <!-- Bootstrap & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 m-0 px-3 login-page-bg">

    <div class="card auth-card bg-white">
        
        <!-- Header Section -->
        <div class="card-header bg-transparent border-bottom px-4 pt-4 pb-3 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="Sybella Logo" class="auth-logo mb-3" onerror="this.style.display='none'">
            <h2 class="auth-title m-0">EMPLOYEE LOGIN</h2>
        </div>

        <!-- Body Section -->
        <div class="card-body p-4">

            @if(session('error') || $errors->any())
                <div class="alert alert-danger py-2 px-3 text-center" style="font-size: 14px;" role="alert">
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            <form id="employeeLoginForm" method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf

                <!-- Employee Code Input -->
                <div class="mb-3">
                    <label class="auth-form-label" for="employee_code">Employee Code</label>
                    <div class="icon-input-wrapper">
                        <i class="fa-solid fa-id-badge left-icon"></i>
                        <input type="text" name="employee_code" id="employee_code" class="form-control py-2" placeholder="Enter Employee Code" value="{{ old('employee_code') }}">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <label class="auth-form-label" for="password">Password</label>
                    <div class="icon-input-wrapper">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" name="password" id="password" class="form-control py-2" placeholder="Enter Password">
                        <i class="fa-solid fa-eye-slash toggle-password" title="Show/Hide Password"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-submit-auth w-100 py-2">Log in</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    
    <!-- App Script (Jisme ab login ki script bhi hai) -->
    <script src="{{ asset('js/script.js') }}"></script>

</body>
</html>