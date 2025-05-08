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
    @if(Auth::check() && Auth::user()->profile_picture)
      <div class="profile-pic">
        <img src="{{ Auth::user()->profile_picture }}" alt="Profile Picture" class="sidebar-profile-pic">
      </div>
    @else
      <div class="profile-pic">
        <i class="fas fa-user-circle sidebar-profile-icon"></i>
      </div>
    @endif
    <span>{{ Auth::user()->name ?? 'User' }}</span>
    <a href="{{ route('logout') }}" class="logout">Logout</a>
  </div>
</div>

<style>
  .sidebar .user-section {
  display: flex;
  align-items: center;
  padding: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: auto;
}

.sidebar .profile-pic {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  margin-right: 10px;
  background-color: #2c3e50;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar .sidebar-profile-pic {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sidebar .sidebar-profile-icon {
  font-size: 40px;
  color: #ecf0f1;
}

.sidebar .user-section span {
  flex-grow: 1;
  color: white;
  font-weight: 500;
}

.sidebar .logout {
  color: #ecf0f1;
  text-decoration: none;
  font-size: 14px;
  padding: 5px 10px;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.sidebar .logout:hover {
  background-color: rgba(255, 255, 255, 0.1);
}
</style>