<header id="topbar">

    <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
        <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-breadcrumb">
            Admin &nbsp;/&nbsp; <span>@yield('page-title', 'Dashboard')</span>
        </div>
    </div>
    <div class="topbar-right">


        <form action="{{ route('admin.logout') }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-sm-inline">Logout</span>
            </button>
        </form>
    </div>
</header>
