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
    <div class="user-info">
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
    </div>
    <a href="{{ route('logout') }}" class="logout">Logout</a>
  </div>
</div>

<style>
  .sidebar .user-section {
    display: flex;
    flex-direction: column;
    padding: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
    background-color: rgba(0, 0, 0, 0.1);
    gap: 10px;
  }

  .sidebar .user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-left: 5px;
  }

  .sidebar .profile-pic {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    background-color: #2c3e50;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-right: 5px;
  }

  .sidebar .sidebar-profile-pic {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .sidebar .sidebar-profile-icon {
    font-size: 32px;
    color: #3498db;
  }

  .sidebar .user-section span {
    color: #3498db;
    font-weight: 500;
    font-size: 0.9em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    text-align: left;
  }

  .sidebar .logout {
    color: #fff;
    text-decoration: none;
    font-size: 0.9em;
    padding: 6px 12px;
    border-radius: 4px;
    background-color: #e74c3c;
    transition: all 0.3s ease;
    white-space: nowrap;
    text-align: center;
    border: 1px solid #c0392b;
    margin-left: 5px;
  }

  .sidebar .logout:hover {
    background-color: #c0392b;
    border-color: #a93226;
  }
</style>