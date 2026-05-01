<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page-bg">

<div class="position-absolute top-0 end-0 p-3 p-md-4 z-3 d-none d-md-block">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger btn-logout shadow-sm">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </button>
    </form>
</div>

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <div class="col-12 col-md-10 col-lg-8">
        
        <div class="card login-card shadow-lg border-0" id="formContainer">
            <div class="card-body p-4 p-sm-5">
                
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Sybella Logo" class="login-logo img-fluid mb-3">
                    <h4 class="fw-bold text-dark">Video Generation Panel</h4>
                </div>

                <form id="videoForm" action="{{ route('video.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">Select Doctor</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-person-badge-fill"></i></span>
                                <select name="doctor_id" class="form-select custom-input border-0 shadow-none">
                                    <option value="">-- Select Doctor --</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">MSL Number</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" id="msl" class="form-control custom-input" readonly placeholder="Auto-filled MSL">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">Speciality</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-star-fill"></i></span>
                                <input type="text" name="speciality" class="form-control custom-input" placeholder="Enter Speciality">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">Hospital Name</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-building-fill"></i></span>
                                <input type="text" name="hospital_name" class="form-control custom-input" placeholder="Enter Hospital Name">
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-semibold text-secondary">Hospital Address</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-geo-alt-fill"></i></span>
                                <input type="text" name="hospital_address" class="form-control custom-input" placeholder="Enter Hospital Address">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">Doctor Mobile</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-telephone-fill"></i></span>
                                <input type="tel" name="mobile" id="mobileInput" class="form-control custom-input" placeholder="Enter Mobile Number" maxlength="10">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold text-secondary">Language</label>
                            <div class="input-group input-group-custom">
                                <span class="icon-box"><i class="bi bi-translate"></i></span>
                                <select name="language" class="form-select custom-input border-0 shadow-none">
                                    <option value="" selected disabled>-- Select Language --</option>
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Marathi">Marathi</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-semibold text-secondary text-uppercase small upload-label">Upload Photo</label>
                            <div class="upload-box position-relative text-center p-4">
                                <input type="file" name="photo" id="photoInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 upload-input" accept="image/jpeg, image/png">
                                <div class="upload-content">
                                    <i class="bi bi-cloud-arrow-up-fill text-primary upload-icon"></i>
                                    <h5 class="mt-2 fw-bold text-primary mb-1" id="uploadText">Tap to Upload Photo</h5>
                                    <small class="text-muted fw-medium">Supports: JPG, PNG</small>
                                </div>
                            </div>
                            <div id="photoErrorContainer"></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if(session('generated') && session('user_file_id'))
                            <div class="alert alert-success text-center mb-4 border-0 shadow-sm rounded-3" id="successAlert">
                                <h6 class="fw-bold mb-0 text-success"><i class="bi bi-check-circle-fill me-2"></i>Banner & Video Generated Successfully!</h6>
                            </div>
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <a href="{{ route('banner.download', session('user_file_id')) }}" class="btn btn-success py-3 fw-bold shadow-sm rounded-3 flex-fill">
                                    <i class="bi bi-image me-2"></i>Banner Download
                                </a>
                                <a href="{{ route('video.download', session('user_file_id')) }}" class="btn btn-primary py-3 fw-bold shadow-sm rounded-3 flex-fill">
                                    <i class="bi bi-film me-2"></i>Video Download
                                </a>
                            </div>
                        @else
                            <button type="submit" id="submitBtn" class="btn btn-primary w-100 btn-login fw-bold py-3">Generate Banner & Video</button>
                            
                            <div id="dummyButtons" class="d-none justify-content-between gap-3 flex-wrap">
                                <button type="button" class="btn btn-success py-3 fw-bold shadow-sm rounded-3 flex-fill disabled opacity-75">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Banner Download
                                </button>
                                <button type="button" class="btn btn-primary py-3 fw-bold shadow-sm rounded-3 flex-fill disabled opacity-75">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Video Download
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="d-md-none mt-4 pt-3 border-top d-flex justify-content-center">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-logout shadow-sm px-4 py-2 fw-bold">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

<script>
    window.doctorsData = @json($doctors);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="{{ asset('js/script.js') }}"></script>
</body>
</html>