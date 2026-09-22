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

            <div id="liveImportProgress" class="alert alert-info" style="display:none;">
                <strong id="progressStatus">Import starting...</strong>
                <div class="progress mt-3 mb-3" style="height: 18px;">
                    <div id="progressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width: 100%"></div>
                </div>
                <div>
                    <span class="badge bg-dark me-2">Processed: <span id="processedCount">0</span></span>
                    <span class="badge bg-success me-2">Inserted: <span id="insertedCount">0</span></span>
                    <span class="badge bg-primary me-2">Updated: <span id="updatedCount">0</span></span>
                    <span class="badge bg-secondary">Skipped: <span id="skippedCount">0</span></span>
                </div>
                <ul id="progressIssues" class="mb-0 mt-3"></ul>
            </div>

            <form id="userImportForm" action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Type select karo -->
                <select name="import_type" required>
                    <option value="employee" @selected(old('import_type') === 'employee')>Employee</option>
                    <option value="doctor" @selected(old('import_type') === 'doctor')>Doctor</option>
                </select>

                <!-- File -->
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>

                <button id="importButton" type="submit">Import</button>
            </form>

        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('userImportForm');
        const panel = document.getElementById('liveImportProgress');
        const button = document.getElementById('importButton');
        const status = document.getElementById('progressStatus');
        const bar = document.getElementById('progressBar');
        const issues = document.getElementById('progressIssues');
        const counts = {
            processed: document.getElementById('processedCount'),
            inserted: document.getElementById('insertedCount'),
            updated: document.getElementById('updatedCount'),
            skipped: document.getElementById('skippedCount'),
        };

        if (!form || !window.fetch || !window.crypto?.randomUUID) {
            return;
        }

        const renderProgress = (data) => {
            counts.processed.textContent = data.processed ?? 0;
            counts.inserted.textContent = data.inserted ?? 0;
            counts.updated.textContent = data.updated ?? 0;
            counts.skipped.textContent = data.skipped ?? 0;

            issues.innerHTML = '';
            (data.issues ?? []).forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                issues.appendChild(item);
            });

            if (data.status === 'completed') {
                status.textContent = 'Import completed successfully.';
                bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                bar.classList.add('bg-success');
            } else if (data.status === 'failed') {
                status.textContent = data.message ?? 'Import failed.';
                bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                bar.classList.add('bg-danger');
            } else {
                status.textContent = 'Import in progress...';
            }
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const progressId = crypto.randomUUID();
            const formData = new FormData(form);
            formData.append('progress_id', progressId);
            const progressUrl = @json(route('users.import.progress', ['progressId' => '__PROGRESS_ID__']))
                .replace('__PROGRESS_ID__', progressId);

            panel.style.display = 'block';
            button.disabled = true;
            status.textContent = 'Uploading file and starting import...';
            issues.innerHTML = '';
            Object.values(counts).forEach((element) => element.textContent = '0');
            bar.className = 'progress-bar progress-bar-striped progress-bar-animated';
            bar.style.width = '100%';

            const poll = async () => {
                try {
                    const response = await fetch(progressUrl, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (response.ok) {
                        renderProgress(await response.json());
                    }
                } catch (error) {
                    // The upload request remains authoritative if a poll is missed.
                }
            };

            const pollTimer = window.setInterval(poll, 1000);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message ?? 'Import failed.');
                }

                renderProgress({...data.summary, status: 'completed'});
            } catch (error) {
                renderProgress({
                    status: 'failed',
                    message: error.message,
                    processed: counts.processed.textContent,
                    inserted: counts.inserted.textContent,
                    updated: counts.updated.textContent,
                    skipped: counts.skipped.textContent,
                });
            } finally {
                window.clearInterval(pollTimer);
                button.disabled = false;
            }
        });
    })();
</script>
