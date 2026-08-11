<nav id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/logo.png') }}" alt="Logo"
             style="height: 40px; width: auto; object-fit: contain;">
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-th-large"></i></span>
            Dashboard
        </a>

        <div class="nav-section-label">Management</div>

        <!-- Employees -->
        <a href="{{ route('admin.employees.index') }}"
           class="nav-item-link {{ request()->routeIs('admin.employees.index') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            Employees
            <span class="nav-badge">{{ $employeeCount }}</span>
        </a>

        <!-- Doctors -->
        <a href="{{ route('admin.doctors.index') }}"
           class="nav-item-link {{ request()->routeIs('admin.doctors.index') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-user-md"></i></span>
            Doctors
            <span class="nav-badge">{{ $totalUserFiles }}</span>
        </a>

    </div>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>
</nav>
