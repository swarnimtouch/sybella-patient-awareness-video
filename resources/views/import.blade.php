<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Import Users (Excel)</h4>
        </div>

        <div class="card-body">

            {{-- Success Message --}}
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('import_summary'))
                @php($summary = session('import_summary'))
                <div class="alert alert-info">
                    <strong>{{ ucfirst($summary['type']) }} import summary</strong>
                    <div class="mt-2">
                        <span class="badge bg-success me-2">Inserted: {{ $summary['inserted'] }}</span>
                        <span class="badge bg-primary me-2">Updated: {{ $summary['updated'] }}</span>
                        <span class="badge bg-secondary">Skipped: {{ $summary['skipped'] }}</span>
                    </div>

                    @if(!empty($summary['issues']))
                        <ul class="mb-0 mt-3">
                            @foreach($summary['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            {{-- Error Message --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Type select karo -->
                <select name="import_type" required>
                    <option value="employee" @selected(old('import_type') === 'employee')>Employee</option>
                    <option value="doctor" @selected(old('import_type') === 'doctor')>Doctor</option>
                </select>

                <!-- File -->
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>

                <button type="submit">Import</button>
            </form>

        </div>
    </div>
</div>
