<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'HomeBudget' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
  @vite(['resources/css/navigations.css', 'resources/js/fontawesome.js'])
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @vite(['resources/js/currency-handler.js'])
  @stack('scripts')
</head>
<body>


  <!-- Top Navigation Bar with Notification -->
  <header class="top-nav">
    @if(session('budget_warning'))
    <div class="budget-alert {{ session('budget_warning.type') }}">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ session('budget_warning.message') }}</span>
      <span class="alert-close" onclick="dismissBudgetAlert()">×</span>
    </div>
    @endif

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

  <script>
    function dismissBudgetAlert() {
      fetch('/dismiss-budget-warning', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      }).then(() => {
        document.querySelector('.budget-alert')?.remove();
      });
    }
    
    // Auto-dismiss after 15 seconds if not closed
    setTimeout(() => {
      document.querySelector('.budget-alert')?.remove();
    }, 15000);
  </script>

</body>
</html>

