<!DOCTYPE html>
<html>
<head>
    <title>Video Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">

    <h3>Video Generation Panel</h3>

    @if(session('generated') && session('user_file_id'))

        {{-- ✅ Success — Download Buttons --}}
        <div class="alert alert-success mt-3">
            <h5>✅ Banner & Video Generated Successfully!</h5>
            <div class="mt-3 d-flex gap-3">
                <a href="{{ route('banner.download', session('user_file_id')) }}"
                   class="btn btn-success btn-lg">
                    🖼 Banner Download
                </a>
                <a href="{{ route('video.download', session('user_file_id')) }}"
                   class="btn btn-primary btn-lg">
                    🎥 Video Download
                </a>
            </div>
        </div>
    @else

        {{-- 📋 Form --}}
        <form action="{{ route('video.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label>Select Doctor</label>
                <select name="doctor_id" class="form-control" required>
                    <option value="">-- Select Doctor --</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}">
                            {{ $doc->name }} ({{ $doc->msl_number }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>MSL Number</label>
                <input type="text" id="msl" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label>Speciality</label>
                <input type="text" name="speciality" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Hospital Name</label>
                <input type="text" name="hospital_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Hospital Address</label>
                <input type="text" name="hospital_address" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Doctor Mobile</label>
                <input type="text" name="mobile" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Language</label>
                <select name="language" class="form-control">
                    <option>English</option>
                    <option>Hindi</option>
                    <option>Marathi</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Upload Photo</label>
                <input type="file" name="photo" class="form-control" required>
            </div>

            <button class="btn btn-primary">Generate Banner & Video</button>
        </form>

    @endif

</div>

<script>
    const doctors = @json($doctors);
    const select = document.querySelector('[name="doctor_id"]');
    if (select) {
        select.addEventListener('change', function () {
            let doc = doctors.find(d => d.id == this.value);
            document.getElementById('msl').value = doc ? doc.msl_number : '';
        });
    }
</script>

</body>
</html>
