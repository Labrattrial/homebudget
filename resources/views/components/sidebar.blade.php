<div class="sidebar">
  <ul>
    <li class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
      <a href="{{ route('user.dashboard') }}">
        <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('user.expenses') ? 'active' : '' }}">
      <a href="{{ route('user.expenses') }}">
        <i class="fas fa-money-check-alt"></i> <span>Expenses</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('user.analysis') ? 'active' : '' }}">
      <a href="{{ route('user.analysis') }}">
        <i class="fas fa-chart-line"></i> <span>Analysis</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('user.settings') ? 'active' : '' }}">
      <a href="{{ route('user.settings') }}">
        <i class="fas fa-cog"></i> <span>User Profile Settings</span>
      </a>
    </li>
  </ul>
  <div class="user-section">
    <div class="profile-pic"></div>
    <span>User</span>
    <a href="{{ route('logout') }}" class="logout">Logout</a>
  </div>
</div>
