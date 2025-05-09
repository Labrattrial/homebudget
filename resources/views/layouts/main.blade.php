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
  <style>
    .budget-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      border-radius: 8px;
      background-color: #fff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 12px;
      max-width: 400px;
      animation: slideIn 0.3s ease-out;
    }

    .budget-notification.warning {
      border-left: 4px solid #ffc107;
    }

    .budget-notification.exceeded {
      border-left: 4px solid #dc3545;
    }

    .budget-notification i {
      font-size: 1.2em;
    }

    .budget-notification.warning i {
      color: #ffc107;
    }

    .budget-notification.exceeded i {
      color: #dc3545;
    }

    .budget-notification-content {
      flex-grow: 1;
    }

    .budget-notification-title {
      font-weight: 600;
      margin-bottom: 4px;
      color: #2c3e50;
    }

    .budget-notification-message {
      color: #666;
      font-size: 0.9em;
    }

    .budget-notification-close {
      cursor: pointer;
      padding: 4px;
      color: #999;
      transition: color 0.2s;
    }

    .budget-notification-close:hover {
      color: #666;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }

    /* Global notification container */
    #globalBudgetNotifications {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-width: 400px;
    }
  </style>
  <script>
    // Store active notifications
    let activeNotifications = new Set();

    // Function to show budget notification
    function showBudgetNotification(message, type = 'warning', id = null) {
      console.log('Showing notification:', { message, type, id });
      
      const notificationId = id || Date.now().toString();
      
      // If notification with this ID already exists, update it
      const existingNotification = document.getElementById(`budget-notification-${notificationId}`);
      if (existingNotification) {
        console.log('Updating existing notification');
        existingNotification.querySelector('.budget-notification-message').innerHTML = message;
        existingNotification.className = `budget-notification ${type}`;
        return;
      }

      // Create notification container if it doesn't exist
      let container = document.getElementById('globalBudgetNotifications');
      if (!container) {
        console.log('Creating notification container');
        container = document.createElement('div');
        container.id = 'globalBudgetNotifications';
        document.body.appendChild(container);
      }

      // Create new notification
      const notification = document.createElement('div');
      notification.id = `budget-notification-${notificationId}`;
      notification.className = `budget-notification ${type}`;
      
      const icon = type === 'exceeded' ? 'fa-times-circle' : 'fa-exclamation-circle';
      
      notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="budget-notification-content">
          <div class="budget-notification-title">Budget Alert</div>
          <div class="budget-notification-message">${message}</div>
        </div>
        <div class="budget-notification-close" onclick="removeBudgetNotification('${notificationId}')">
          <i class="fas fa-times"></i>
        </div>
      `;

      console.log('Appending notification to container');
      container.appendChild(notification);
      activeNotifications.add(notificationId);

      // Auto remove after 10 seconds
      setTimeout(() => {
        removeBudgetNotification(notificationId);
      }, 10000);
    }

    // Function to remove a specific notification
    function removeBudgetNotification(id) {
      const notification = document.getElementById(`budget-notification-${id}`);
      if (notification) {
        notification.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => {
          notification.remove();
          activeNotifications.delete(id);
        }, 300);
      }
    }

    // Function to update budget notifications
    function updateBudgetNotifications() {
      console.log('Fetching budget status...');
      fetch('{{ route("budget.status") }}')
        .then(response => response.json())
        .then(data => {
          console.log('Budget status data:', data);
          
          // Clear existing notifications
          activeNotifications.forEach(id => removeBudgetNotification(id));
          
          // Check total budget - convert strings to numbers for comparison
          const totalSpent = parseFloat(data.totalSpent);
          const totalBudget = parseFloat(data.totalBudget);
          
          console.log('Total spent:', totalSpent, 'Total budget:', totalBudget);
          
          if (totalSpent > totalBudget) {
            console.log('Budget exceeded, showing notification');
            const message = `<i class="fas fa-exclamation-triangle"></i> Over budget! Spent ₱${totalSpent.toLocaleString()} of ₱${totalBudget.toLocaleString()}`;
            showBudgetNotification(message, 'exceeded', 'total-budget');
          }
          
          // Check category budgets
          data.categoryWarnings.forEach(warning => {
            if (warning.isExceeded) {
              console.log('Category budget exceeded:', warning.name);
              const message = `<i class="fas fa-exclamation-triangle"></i> ${warning.name} budget exceeded!`;
              showBudgetNotification(message, 'exceeded', `category-${warning.name.toLowerCase().replace(/\s+/g, '-')}`);
            }
          });
        })
        .catch(error => {
          console.error('Error updating budget notifications:', error);
        });
    }

    // Update notifications every 30 seconds
    setInterval(updateBudgetNotifications, 30000);

    // Initial update
    document.addEventListener('DOMContentLoaded', () => {
      console.log('DOM loaded, updating notifications');
      updateBudgetNotifications();
      // Also update notifications when the page becomes visible
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          console.log('Page visible, updating notifications');
          updateBudgetNotifications();
        }
      });
    });
  </script>
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
  </header>

  <!-- Layout Container -->
  <div class="container">
    @include('components.sidebar')
    <div class="content-area">
      @yield('content')
    </div>
  </div>

  <!-- Notification System -->
  <div id="notification" class="notification" style="display: none;"></div>

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

    // Global notification handler
    function showNotification(message, type = 'success') {
      const notification = document.getElementById('notification');
      if (!notification) return;

      // Set message and type
      notification.textContent = message;
      notification.className = `notification ${type}`;
      
      // Show notification
      notification.style.display = 'flex';
      
      // Add icon based on type
      const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
      notification.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
      
      // Auto hide after 3 seconds
      setTimeout(() => {
        notification.style.display = 'none';
      }, 3000);
    }
  </script>

</body>
</html>

