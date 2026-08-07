@extends('layouts.admin')

@section('title', 'Doctors')
@section('page-title', 'Doctors')

@section('content')

    <div class="page-header">
        <div class="page-title-group">
            <h4>Doctors</h4>
            <p>Manage all registered doctors in the system</p>
        </div>
        <a href="{{ route('admin.doctors.export') }}{{ request('search') ? '?search='.request('search') : '' }}"
           class="btn-add btn-export">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <div class="filter-bar">
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text"
                   id="liveSearch"
                   value="{{ request('search') }}"
                   class="filter-input"
                   placeholder="Type to search by name or Employee ID..."
                   autocomplete="off">
            <span class="search-spinner" id="searchSpinner"></span>
        </div>
    </div>

    {{-- ════ DESKTOP TABLE ════ --}}
    <div class="glass-card desktop-view">
        <div class="table-wrap">
            <table class="doc-table">
                <thead>
                <tr>
                    <th>SR NO.</th>
                    <th>Employee Name</th>
                    <th>Employee Code</th>
                    <th>Doctor Name</th>
                    <th>MSL Code</th>
                    <th>Mobile</th>
                    <th>Speciality</th>
                    <th>Hospital</th>
                    <th>Address</th>
                    <th>Language</th>
                    <th>Photo</th>
                    <th>Banner</th>
                    <th>Video</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($doctors as $index => $doctor)
                    @php
                        $file     = $doctor->userFile;
                        $photoUrl = $file && $file->photo ? asset('storage/'.$file->photo) : null;
                    @endphp
                    <tr>
                        <td class="serial-cell">{{ $doctors->firstItem() + $index }}</td>

                        <td>{{ $doctor->employee->name ?? '—' }}</td>
                        <td>{{ $doctor->employee->employee_code ?? '—' }}</td>

                        <td><strong>{{ $doctor->name }}</strong></td>
                        <td><strong>{{ $doctor->msl_number }}</strong></td>
                        <td class="text-muted-sm">{{ $doctor->mobile ?? '—' }}</td>
                        <td>{{ $doctor->speciality ?? '—' }}</td>
                        <td>{{ $doctor->hospital_name ?? '—' }}</td>
                        <td>{{ $doctor->address ?? '—' }}</td>
                        <td>{{ $file->language ?? '—' }}</td>

                        <td>
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}"
                                     style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                            @else —
                            @endif
                        </td>

                        <td>
                            @if($file && $file->banner_path)
                                <a href="{{ asset('storage/'.$file->banner_path) }}"
                                   download class="btn btn-sm btn-success">
                                    <i class="fas fa-image"></i> Banner
                                </a>
                            @else —
                            @endif
                        </td>

                        <td>
                            @if($file && $file->video)
                                <a href="{{ asset('storage/'.$file->video) }}"
                                   download class="btn btn-sm btn-primary">
                                    <i class="fas fa-video"></i> Video
                                </a>
                            @else —
                            @endif
                        </td>

                        <td class="text-muted-sm" style="font-size:0.75rem;">
                            {{ $doctor->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                        </td>

                        <td>
                            <div class="action-btns">
                                <form action="{{ route('admin.doctors.destroy', $doctor->id) }}"
                                      method="POST" class="delete-form">
                                    @csrf
                                    <button type="button"
                                            class="act-btn del btn-delete"
                                            data-name="{{ $doctor->name }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15">
                            <div class="empty-state">
                                <i class="fas fa-user-md"></i>
                                <h5>No records found</h5>
                                <p>No doctors available</p>
                            </div>
                        </td>
                    </tr>
                @endforelse

                {{-- Shown by JS when live search finds no match --}}
                <tr id="noSearchResults" style="display:none;">
                    <td colspan="15">
                        <div class="empty-state">
                            <i class="fas fa-user-md"></i>
                            <h5>No records found</h5>
                            <p>No doctor matches your search</p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        @if($doctors->hasPages())
            <div class="pagination-wrap">
                <div class="page-info">
                    Showing {{ $doctors->firstItem() }}–{{ $doctors->lastItem() }} of {{ $doctors->total() }}
                </div>
                <div class="custom-pagination">
                    @if($doctors->onFirstPage())
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;">
                            <i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $doctors->previousPageUrl() }}" class="page-btn">
                            <i class="fas fa-chevron-left"></i></a>
                    @endif
                    @foreach($doctors->getUrlRange(1, $doctors->lastPage()) as $page => $url)
                        <a href="{{ $url }}"
                           class="page-btn {{ $page == $doctors->currentPage() ? 'active' : '' }}">
                            {{ $page }}</a>
                    @endforeach
                    @if($doctors->hasMorePages())
                        <a href="{{ $doctors->nextPageUrl() }}" class="page-btn">
                            <i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn" style="opacity:0.4;cursor:not-allowed;">
                            <i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ════ MOBILE CARDS ════ --}}
    <div class="mobile-view">

        @forelse($doctors as $index => $doctor)
            @php
                $file     = $doctor->userFile;
                $photoUrl = $file && $file->photo ? asset('storage/'.$file->photo) : null;
            @endphp

            <div class="m-card" style="animation-delay:{{ $index * 0.04 }}s;">

                {{-- HEADER --}}
                <div class="m-card-header">
                    <div class="m-card-title">
                        <div class="m-card-name">{{ $doctor->name }}</div>
                        <div class="m-card-sub">
                            <i class="fas fa-id-badge"></i> {{ $doctor->msl_number }}
                        </div>
                        <div class="m-card-sub">
                            <i class="fas fa-phone"></i> {{ $doctor->mobile ?? '—' }}
                        </div>
                    </div>
                    <span class="m-card-serial-badge">#{{ $doctors->firstItem() + $index }}</span>
                </div>

                {{-- 1. EMPLOYEE DETAILS --}}
                <div class="m-section-label employee">
                    <i class="fas fa-user-tie"></i> Employee Details
                </div>
                <div class="m-card-body">
                    <div class="m-fields-grid">
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-user"></i> Employee Name
                            </div>
                            <div class="m-field-value">
                                {{ $doctor->employee->name ?? '—' }}
                            </div>
                        </div>
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-id-card"></i> Employee Code
                            </div>
                            <div class="m-field-value">
                                {{ $doctor->employee->employee_code ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. DOCTOR DETAILS --}}
                <div class="m-section-label doctor">
                    <i class="fas fa-user-md"></i> Doctor Details
                </div>
                <div class="m-card-body">
                    <div class="m-fields-grid">
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-stethoscope"></i> Speciality
                            </div>
                            <div class="m-field-value {{ $doctor->speciality ? '' : 'muted' }}">
                                {{ $doctor->speciality ?? 'Not set' }}
                            </div>
                        </div>
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-hospital"></i> Hospital
                            </div>
                            <div class="m-field-value {{ $doctor->hospital_name ? '' : 'muted' }}">
                                {{ $doctor->hospital_name ?? 'Not set' }}
                            </div>
                        </div>
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-map-marker-alt"></i> Address
                            </div>
                            <div class="m-field-value {{ $doctor->address ? '' : 'muted' }}">
                                {{ $doctor->address ?? 'Not set' }}
                            </div>
                        </div>
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-language"></i> Language
                            </div>
                            <div class="m-field-value">
                                {{ $file->language ?? '—' }}
                            </div>
                        </div>
                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-calendar-alt"></i> Created
                            </div>
                            <div class="m-field-value" style="font-size:0.75rem;">
                                {{ $doctor->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. MEDIA DETAILS --}}
                <div class="m-section-label" style="background:rgba(99,102,241,0.15);color:#818cf8;">
                    <i class="fas fa-photo-video"></i> Media Details
                </div>
                <div class="m-card-body">
                    <div class="m-fields-grid">

                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-camera"></i> Photo
                            </div>
                            <div class="m-field-value">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}"
                                         style="width:60px;height:60px;border-radius:8px;object-fit:cover;">
                                @else —
                                @endif
                            </div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-image"></i> Banner
                            </div>
                            <div class="m-field-value">
                                @if($file && $file->banner_path)
                                    <a href="{{ asset('storage/'.$file->banner_path) }}"
                                       download class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                @else —
                                @endif
                            </div>
                        </div>

                        <div class="m-field">
                            <div class="m-field-label">
                                <i class="fas fa-video"></i> Video
                            </div>
                            <div class="m-field-value">
                                @if($file && $file->video)
                                    <a href="{{ asset('storage/'.$file->video) }}"
                                       download class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                @else —
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="m-card-footer">
                    <div class="m-card-date">
                        <i class="fas fa-clock me-1"></i>
                        {{ $doctor->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                    </div>
                    <form action="{{ route('admin.doctors.destroy', $doctor->id) }}"
                          method="POST" class="delete-form">
                        @csrf
                        <button type="button"
                                class="btn-del-mobile btn-delete"
                                data-name="{{ $doctor->name }}">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                </div>

            </div>

        @empty
            <div class="glass-card">
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <h5>No records found</h5>
                    <p>No doctors available</p>
                </div>
            </div>
        @endforelse

        {{-- Shown by JS when live search finds no match (mobile) --}}
        <div class="glass-card" id="noSearchResultsMobile" style="display:none;">
            <div class="empty-state">
                <i class="fas fa-user-md"></i>
                <h5>No records found</h5>
                <p>No doctor matches your search</p>
            </div>
        </div>

        @if($doctors->hasPages())
            <div class="pagination-wrap" style="border:none;padding:4px 0 16px;">
                <div class="page-info">
                    {{ $doctors->firstItem() }}–{{ $doctors->lastItem() }} of {{ $doctors->total() }}
                </div>
                <div class="custom-pagination">
                    @if($doctors->onFirstPage())
                        <span class="page-btn" style="opacity:0.4;">
                            <i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $doctors->previousPageUrl() }}" class="page-btn">
                            <i class="fas fa-chevron-left"></i></a>
                    @endif
                    @foreach($doctors->getUrlRange(1, $doctors->lastPage()) as $page => $url)
                        <a href="{{ $url }}"
                           class="page-btn {{ $page == $doctors->currentPage() ? 'active' : '' }}">
                            {{ $page }}</a>
                    @endforeach
                    @if($doctors->hasMorePages())
                        <a href="{{ $doctors->nextPageUrl() }}" class="page-btn">
                            <i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn" style="opacity:0.4;">
                            <i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif

    </div>

    {{-- Photo Modal --}}
    <div class="photo-modal-overlay" id="photoModal" style="display:none;">
        <div class="photo-modal-box">
            <button class="photo-modal-close"
                    onclick="document.getElementById('photoModal').classList.remove('open')">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalImgWrap"></div>
            <div class="photo-modal-name" id="modalName"></div>
            <div class="photo-modal-empid" id="modalEmpId"></div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* Ensures the photo modal stays hidden until explicitly opened */
        .photo-modal-overlay { display: none; }
        .photo-modal-overlay.open {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ✅ closePhotoModal fix
        function closePhotoModal(e) {
            if (e.target === document.getElementById('photoModal')) {
                document.getElementById('photoModal').classList.remove('open');
            }
        }
        const photoModal = document.getElementById('photoModal');
        if (photoModal) {
            photoModal.addEventListener('click', closePhotoModal);
        }

        // DELETE CONFIRM
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form    = btn.closest('.delete-form');
            const docName = btn.getAttribute('data-name') || 'this doctor';
            Swal.fire({
                title: 'Delete Doctor?',
                html: `Are you sure you want to delete <strong>${docName}</strong>?<br><small style="color:#aaa;">This action cannot be undone.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Yes, Delete',
                cancelButtonText:  '<i class="fas fa-times"></i> Cancel',
                confirmButtonColor: '#e74a3b',
                cancelButtonColor:  '#4e73df',
                background: '#1a2035',
                color: '#e8eaf6',
                iconColor: '#f6c23e',
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

        // SUCCESS TOAST
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

        // CLIENT-SIDE LIVE SEARCH (URL stays unchanged)
        (function () {
            const input   = document.getElementById('liveSearch');
            const spinner = document.getElementById('searchSpinner');
            if (!input) return;

            const desktopRows = Array.from(document.querySelectorAll('.desktop-view tbody tr'))
                .filter(row => !row.querySelector('.empty-state') && row.id !== 'noSearchResults');
            const mobileCards = Array.from(document.querySelectorAll('.mobile-view .m-card'));
            const pagination  = document.querySelectorAll('.pagination-wrap');

            const noResultsRow    = document.getElementById('noSearchResults');
            const noResultsMobile = document.getElementById('noSearchResultsMobile');

            input.value = '';
            input.addEventListener('input', function () {
                const query = this.value.trim().toLocaleLowerCase();
                if (spinner) spinner.style.display = 'block';

                let visibleDesktop = 0;
                desktopRows.forEach(row => {
                    const match = row.textContent.toLocaleLowerCase().includes(query);
                    row.style.display = match ? '' : 'none';
                    if (match) visibleDesktop++;
                });

                let visibleMobile = 0;
                mobileCards.forEach(card => {
                    const match = card.textContent.toLocaleLowerCase().includes(query);
                    card.style.display = match ? '' : 'none';
                    if (match) visibleMobile++;
                });

                pagination.forEach(item => {
                    item.style.display = query ? 'none' : '';
                });

                if (noResultsRow) {
                    noResultsRow.style.display = (query && visibleDesktop === 0) ? '' : 'none';
                }
                if (noResultsMobile) {
                    noResultsMobile.style.display = (query && visibleMobile === 0) ? '' : 'none';
                }

                if (spinner) spinner.style.display = 'none';
            });
        })();

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('photoModal');
                if (modal) modal.classList.remove('open');
            }
        });
    </script>
@endpush
