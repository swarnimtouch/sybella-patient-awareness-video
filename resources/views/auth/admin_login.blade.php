<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonalta - Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at center, #dcedfb 0%, #b3d7f6 100%);
        }

        .login-card {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1.5px solid #204e8a;
            max-width: 420px;
            width: 100%;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        .login-title {
            color: #204e8a;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .form-label {
            color: #204e8a;
            font-size: 14px;
            font-weight: 600;
        }

        .icon-input-wrapper {
            position: relative;
        }

        .icon-input-wrapper .left-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #204e8a;
            font-size: 14px;
        }

        .icon-input-wrapper .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #204e8a;
            font-size: 14px;
            cursor: pointer;
            z-index: 5;
            transition: color 0.3s;
        }

        .icon-input-wrapper .toggle-password:hover {
            color: #3461a3;
        }

        .icon-input-wrapper .form-control {
            padding-left: 45px;
            font-size: 14px;
            border-color: #cbd5e1;
        }

        .icon-input-wrapper #password {
            padding-right: 40px;
        }

        .icon-input-wrapper .form-control:focus {
            border-color: #204e8a;
            box-shadow: 0 0 0 0.2rem rgba(32, 78, 138, 0.25);
        }

        .remember-label {
            font-size: 13px;
            color: #204e8a;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 0;
            margin-left: 8px;
        }

        .btn-submit {
            background-color: #3461a3;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #204e8a;
            color: #ffffff;
        }

        label.error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: block;
            font-weight: 500;
        }

        .form-control.error {
            border-color: #dc3545;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100 m-0 px-3">

    <div class="card login-card bg-white">

        <div class="card-header bg-transparent border-bottom px-4 pt-4 pb-3 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="Zonalta Logo" class="logo mb-3" onerror="this.style.display='none'">
            <h2 class="login-title m-0">ADMIN LOGIN</h2>
        </div>

        <div class="card-body p-4">

            @if(session('error') || $errors->any())
                <div class="alert alert-danger py-2 px-3 text-center" style="font-size: 14px;" role="alert">
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            <form id="adminLoginForm" method="POST" action="{{ route('admin.login.submit') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="icon-input-wrapper">
                        <i class="fa-solid fa-envelope left-icon"></i>
                        <input type="email" name="email" id="email" class="form-control py-2" placeholder="Enter Email Address" value="{{ old('email') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="icon-input-wrapper">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" name="password" id="password" class="form-control py-2" placeholder="Enter Password">
                        <i class="fa-solid fa-eye-slash toggle-password" title="Show/Hide Password"></i>
                    </div>
                </div>

                <div class="mb-4 d-flex align-items-center">
                    <input type="checkbox" name="remember" id="remember" style="accent-color: #204e8a; cursor: pointer;">
                    <label class="remember-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-submit w-100 py-2">Log in</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <script>
        $(document).ready(function () {

            $(".toggle-password").click(function() {
                $(this).toggleClass("fa-eye-slash fa-eye");
                var input = $("#password");
                if (input.attr("type") === "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }
            });

            $("#adminLoginForm").validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    email: {
                        required: "Please enter your email address",
                        email: "Please enter a valid email"
                    },
                    password: {
                        required: "Please enter your password",
                        minlength: "Password must be at least 6 characters"
                    }
                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element.parent(".icon-input-wrapper"));
                }
            });
        });
    </script>

</body>
</html>
