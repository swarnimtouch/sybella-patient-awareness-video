@extends('layouts.admin')

@section('title', 'Employees')
@section('page-title', 'Employees')

@push('styles')
@endpush

@section('content')

    {{-- ── Page Header ── --}}
    <div class="page-header">
        <div class="page-title-group">
            <h4>Employees</h4>
            <p>Manage all registered employees in the system</p>
        </div>
        <a href="{{ route('admin.employees.export') }}{{ request('search') ? '?search='.request('search') : '' }}"
           class="btn-add btn-export">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    {{-- ── Success Alert ── --}}
    @if(session('success'))
        <div class="alert-success-bar">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Live Search Bar ── --}}
    <div class="filter-bar">
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text"
                   id="liveSearch"
                   value="{{ request('search') }}"
                   class="filter-input"
                   placeholder="Type to search by name or Employee Code..."
                   autocomplete="off">
            <span class="search-spinner" id="searchSpinner"></span>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         DESKTOP TABLE (≥ 768px)
    ════════════════════════════════════════ --}}
    <div class="glass-card desktop-view">
        <div class="table-wrap">
            <table class="doc-table">
                <thead>
                <tr>
                    <th>SR NO.</th>
                    <th class="th-doctor">Employee Name</th>
                    <th class="th-doctor">Employee Code</th>
                    <th class="th-doctor">Position Code</th>
                    <th class="th-doctor">Designation</th>
                    <th class="th-employee ">HQ Name</th>
                    <th class="th-employee">HQ Code</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $index => $employee)
                    <tr>
                        <td class="serial-cell">{{ $employees->firstItem() + $index }}</td>

                        <td>
                            <div class="doc-name-cell">
                                <span class="doc-name-text">{{ $employee->name ?? '—' }}</span>
                            </div>
                        </td>

                        <td><span class="badge-mono emp">{{ $employee->employee_code ?? '—' }}</span></td>
                        <td><span class="badge-mono">{{ $employee->position_code ?? '—' }}</span></td>
                        <td style="font-weight:500;">{{ $employee->designation ?? '—' }}</td>
                        <td class="text-muted-sm">{{ $employee->hq_name ?? '—' }}</td>
                        <td><span class="badge-mono">{{ $employee->hq_code ?? '—' }}</span></td>

                        <td class="text-muted-sm" style="font-size:0.75rem; white-space:nowrap;">
                            {{ $employee->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                        </td>

                        <td>
                            <div class="action-btns">
                                <form action="{{ route('admin.employees.destroy', $employee->id) }}"
                                      method="POST"
                                      class="delete-form">
                                    @csrf
                                    <button type="button"
                                            class="act-btn del btn-delete"
                                            data-name="{{ $employee->name }}"
                                            title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h5>No records found</h5>
                                <p>No employees have been added yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="pagination-wrap">
                <div class="page-info">
                    Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
                </div>
                <div class="custom-pagination">
                    @if($employees->onFirstPage())
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $employees->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @foreach($employees->getUrlRange(1, $employees->lastPage()) as $page => $url)
                        <a href="{{ $url }}" class="page-btn {{ $page == $employees->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                    @if($employees->hasMorePages())
                        <a href="{{ $employees->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════
         MOBILE CARDS (< 768px)
    ════════════════════════════════════════ --}}
    <div class="mobile-view">

        @forelse($employees as $index => $employee)
            @php
                $colors = ['c1','c2','c3','c4','c5'];
                $c      = $colors[$index % 5];
            @endphp

            <div class="m-card" style="animation-delay:{{ $index * 0.04 }}s;">

                {{-- Card Header --}}
                <div class="m-card-header">
                    <div class="m-card-av {{ $c }}">{{ strtoupper(substr($employee->name, 0, 1)) }}</div>
                    <div class="m-card-title">
                        <div class="m-card-name">{{ $employee->name }}</div>
                        <div class="m-card-sub">{{ $employee->employee_code ?? '—' }}</div>
                    </div>
                    <span class="m-card-serial-badge">#{{ $employees->firstItem() + $index }}</span>
                </div>

                {{-- Employee Details --}}
                <div class="m-section-label employee">
                    <i class="fas fa-id-card"></i> Employee Details
                </div>
                <div class="m-card-body">
                    <div class="m-fields-grid">

                        <div class="m-field">
                            <div class="m-field-label"><i class="fas fa-id-badge"></i> Employee Code</div>
                            <div class="m-field-value mono-emp">{{ $employee->employee_code ?? '—' }}</div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label"><i class="fas fa-briefcase"></i> Position Code</div>
                            <div class="m-field-value {{ $employee->position_code ? '' : 'muted' }}">
                                {{ $employee->position_code ?? 'Not set' }}
                            </div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label"><i class="fas fa-user-tie"></i> Designation</div>
                            <div class="m-field-value {{ $employee->designation ? '' : 'muted' }}">
                                {{ $employee->designation ?? 'Not set' }}
                            </div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label"><i class="fas fa-building"></i> HQ Name</div>
                            <div class="m-field-value {{ $employee->hq_name ? '' : 'muted' }}">
                                {{ $employee->hq_name ?? 'Not set' }}
                            </div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label"><i class="fas fa-barcode"></i> HQ Code</div>
                            <div class="m-field-value {{ $employee->hq_code ? '' : 'muted' }}">
                                {{ $employee->hq_code ?? 'Not set' }}
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="m-card-footer">
                    <div class="m-card-date">
                        <i class="fas fa-calendar me-1"></i>
                        {{ $employee->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                    </div>
                    <form action="{{ route('admin.employees.destroy', $employee->id) }}"
                          method="POST"
                          class="delete-form">
                        @csrf
                        <button type="button"
                                class="btn-del-mobile btn-delete"
                                data-name="{{ $employee->name }}">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                </div>

            </div>

        @empty
            <div class="glass-card">
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h5>No records found</h5>
                    <p>No Employees have been added yet.</p>
                </div>
            </div>
        @endforelse

        @if($employees->hasPages())
            <div class="pagination-wrap" style="border:none; padding:4px 0 16px;">
                <div class="page-info">
                    {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
                </div>
                <div class="custom-pagination">
                    @if($employees->onFirstPage())
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $employees->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @foreach($employees->getUrlRange(1, $employees->lastPage()) as $page => $url)
                        <a href="{{ $url }}" class="page-btn {{ $page == $employees->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                    @if($employees->hasMorePages())
                        <a href="{{ $employees->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ══════════════════════════════════════
        // SWEET ALERT DELETE CONFIRM
        // ══════════════════════════════════════
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();

            const form    = btn.closest('.delete-form');
            const empName = btn.getAttribute('data-name') || 'this Employee';

            Swal.fire({
                title: 'Delete Employee?',
                html: `Are you sure you want to delete <strong>${empName}</strong>?<br><small style="color:#aaa;">This action cannot be undone.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Yes, Delete',
                cancelButtonText:  '<i class="fas fa-times"></i> Cancel',
                confirmButtonColor: '#e74a3b',
                cancelButtonColor:  '#4e73df',
                background: '#1a2035',
                color: '#e8eaf6',
                iconColor: '#f6c23e',
                customClass: {
                    popup:         'swal-custom-popup',
                    title:         'swal-custom-title',
                    confirmButton: 'swal-confirm-btn',
                    cancelButton:  'swal-cancel-btn',
                },
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        background: '#1a2035',
                        color: '#e8eaf6',
                        didOpen: function () { Swal.showLoading(); }
                    });
                    form.submit();
                }
            });
        });

        // ══════════════════════════════════════
        // SUCCESS TOAST
        // ══════════════════════════════════════
        @if(session('success'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: '#1a2035',
            color: '#1cc88a',
            iconColor: '#1cc88a',
        });
        @endif

        // ══════════════════════════════════════
        // LIVE SEARCH
        // ══════════════════════════════════════
        (function () {
            const input   = document.getElementById('liveSearch');
            const spinner = document.getElementById('searchSpinner');
            if (!input) return;
            let timer = null;
            input.addEventListener('keyup', function () {
                clearTimeout(timer);
                const query = this.value.trim();
                spinner.style.display = 'block';
                timer = setTimeout(function () {
                    const baseUrl = '{{ route('admin.employees.index') }}';
                    const url = query.length > 0
                        ? baseUrl + '?search=' + encodeURIComponent(query)
                        : baseUrl;
                    window.location.href = url;
                }, 400);
            });
        })();
    </script>
@endpush
