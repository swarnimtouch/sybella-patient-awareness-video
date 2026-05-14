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
                <select name="import_type">
                    <option value="employee">Employee</option>
                    <option value="doctor">Doctor</option>
                </select>

                <!-- File -->
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>

                <button type="submit">Import</button>
            </form>

        </div>
    </div>
</div>

