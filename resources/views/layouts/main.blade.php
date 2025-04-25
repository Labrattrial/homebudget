<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $title ?? 'HomeBudget' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
  @vite(['resources/css/dashboard.css', 'resources/js/fontawesome.js'])
  @yield('scripts')
</head>
<body>
  <!-- Top Navigation Bar -->
  <header class="top-nav">
    <div class="app-info">
      <i class="fas fa-wallet"></i> <span>HomeBudget</span>
    </div>
    <div class="user-info">
      <div class="profile-pic"></div>
      <span>User Placeholder</span>
    </div>
  </header>

  <!-- Layout Container -->
  <div class="container">
    @include('components.sidebar')
    <div class="content-area">
      @yield('content')
    </div>
  </div>
</body>
</html>
