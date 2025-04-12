<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  @vite(['resources/css/dashboard.css'])
</head>
<body>
  <div class="sidebar">
    <div class="logo"><i class="fas fa-wallet"></i> HomeBudget</div>
    <ul>
      <li class="active"><i class="fas fa-chart-pie"></i> Dashboard</li>
      <li><i class="fas fa-money-check-alt"></i> Expenses</li>
      <li><i class="fas fa-chart-line"></i> Analysis</li>
      <li><i class="fas fa-cog"></i> Settings</li>
    </ul>
    <div class="user-section">
      <div class="profile-pic"></div>
      <span>{{ session('user')['name'] ?? 'User' }}</span>
      <a href="{{ route('logout') }}" class="logout">Logout</a>
    </div>
  </div>

  <div class="dashboard-content">
    <div class="welcome-card">
      <div>
        <h1>Welcome, {{ session('user')['name'] ?? 'User' }}!</h1>
        <p>Here's a quick overview of your finances</p>
      </div>
      <i class="fas fa-coins fa-3x"></i>
    </div>

    <div class="stats">
      <div class="card">
        <i class="fas fa-piggy-bank fa-2x" style="color:#4b8dbf;"></i>
        <h3>MY NET WORTH</h3>
        <p>₱25,000</p>
        <h3>TOTAL EXPENSE</h3>
        <p>₱15,100</p>
        <small>Last 30 Days</small>
      </div>

      <div class="card">
        <i class="fas fa-chart-pie fa-2x" style="color:#4b8dbf;"></i>
        <h3>CATEGORY BREAKDOWN</h3>
        <div class="fake-pie"></div>
      </div>

      <div class="card">
        <i class="fas fa-chart-bar fa-2x" style="color:#4b8dbf;"></i>
        <h3>MONTHLY EXPENSES</h3>
        <div class="fake-bar-graph">
          <div class="bar" style="height: 40%"></div>
          <div class="bar" style="height: 60%"></div>
          <div class="bar" style="height: 80%"></div>
          <div class="bar" style="height: 30%"></div>
          <div class="bar" style="height: 70%"></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
