<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page-bg">

<div class="position-absolute top-0 end-0 p-3 p-md-4 z-3 d-none d-md-block">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger btn-logout shadow-sm">
            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
        </button>
    </form>
</div>

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <div class="col-12 col-md-10 col-lg-8">
        
        <div class="card video-auth-card" id="formContainer">
            <div class="card-header bg-transparent border-bottom px-4 pt-4 pb-3 text-center">
                <img src="{{ asset('images/logo.png') }}" alt="Sybella Logo" class="auth-logo mb-3" onerror="this.style.display='none'">
                <h2 class="auth-title m-0">VIDEO GENERATION PANEL</h2>
            </div>
            
            <div class="card-body p-4 p-sm-5">
                <form id="videoForm" action="{{ route('video.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">Select Doctor</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-user-doctor left-icon"></i>
                                <select name="doctor_id" class="form-select py-2">
                                    <option value="">-- Select Doctor --</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">MSL Number</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-barcode left-icon"></i>
                                <input type="text" id="msl" class="form-control py-2" readonly placeholder="Auto-filled MSL">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">Speciality</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-star left-icon"></i>
                                <input type="text" name="speciality" class="form-control py-2" placeholder="Enter Speciality">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">Hospital Name</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-hospital left-icon"></i>
                                <input type="text" name="hospital_name" class="form-control py-2" placeholder="Enter Hospital Name">
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="auth-form-label mb-1">Hospital Address</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-location-dot left-icon"></i>
                                <input type="text" name="hospital_address" class="form-control py-2" placeholder="Enter Hospital Address">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">Doctor Mobile</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-phone left-icon"></i>
                                <input type="tel" name="mobile" id="mobileInput" class="form-control py-2" placeholder="Enter Mobile Number" maxlength="10">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="auth-form-label mb-1">Language</label>
                            <div class="icon-input-wrapper">
                                <i class="fa-solid fa-language left-icon"></i>
                                <select name="language" class="form-select py-2">
                                    <option value="" disabled {{ old('language') ? '' : 'selected' }}>-- Select Language --</option>
                                    <option value="English" {{ old('language') === 'English' ? 'selected' : '' }}>English</option>
                                    <option value="Hindi" {{ old('language') === 'Hindi' ? 'selected' : '' }}>Hindi</option>
                                    <option value="Marathi" {{ old('language') === 'Marathi' ? 'selected' : '' }}>Marathi</option>
                                </select>
                            </div>
                            @error('language')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="auth-form-label mb-1 text-uppercase small">Upload Photo</label>
                            <div class="upload-box position-relative text-center p-4 mt-1">
                                <input type="file" name="photo" id="photoInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 upload-input" accept="image/jpeg, image/png">
                                <div class="upload-content">
                                    <i class="fa-solid fa-cloud-arrow-up upload-icon mb-2"></i>
                                    <h5 class="fw-bold mb-1" id="uploadText" style="color: #204e8a;">Tap to Upload Photo</h5>
                                    <small class="text-muted fw-medium">Supports: JPG, PNG</small>
                                </div>
                            </div>
                            <div id="photoErrorContainer"></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if(session('generated') && session('user_file_id'))
                            <div class="alert alert-success text-center mb-4 border-0 shadow-sm rounded-3" id="successAlert">
                                <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-circle-check me-2"></i>Banner & Video Generated Successfully!</h6>
                            </div>
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <a href="{{ route('banner.download', session('user_file_id')) }}" class="btn btn-success py-3 fw-bold shadow-sm rounded-3 flex-fill">
                                    <i class="fa-solid fa-image me-2"></i>Banner Download
                                </a>
                                <a href="{{ route('video.download', session('user_file_id')) }}" class="btn btn-primary py-3 fw-bold shadow-sm rounded-3 flex-fill">
                                    <i class="fa-solid fa-film me-2"></i>Video Download
                                </a>
                            </div>
                        @else
                            <button type="submit" id="submitBtn" class="btn btn-submit-auth w-100 py-3 rounded-3">Generate Banner & Video</button>
                            
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

                <div class="d-md-none mt-4 pt-3 border-top d-flex justify-content-center">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-logout shadow-sm px-4 py-2 fw-bold">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.doctorsData = @json($doctors);
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="{{ asset('js/script.js') }}"></script>
</body>
</html>
