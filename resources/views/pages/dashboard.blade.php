@extends('layouts.main')

@section('content')
@vite(['resources/css/dashboard.css', 'resources/js/chart.js', 'resources/js/fontawesome.js'])
<div class="welcome-card">
  <div>
    <h1>Welcome, User!</h1>
    <p>Here's a quick overview of your finances</p>
  </div>
  <i class="fas fa-coins fa-3x"></i>
</div>

<!-- Static Budget Setting (No backend) -->
<div id="budgetSetter" class="card">
  <h3><i class="fas fa-wallet"></i> SET YOUR MONTHLY BUDGET</h3>
  <div class="budget-input-container">
    <p>Let's get started! Set your total monthly spending limit:</p>
    <div class="input-group">
      <span class="currency">₱</span>
      <input type="number" id="monthlyBudget" placeholder="e.g. 25000" min="0" step="100">
    </div>
    <button id="saveBudget" class="btn-primary">Set Budget</button>
  </div>
</div>

<!-- Static Dashboard (Simulated Data) -->
<div id="budgetDashboard" class="dashboard">
  <div class="card budget-overview">
    <div class="budget-header">
      <h3><i class="fas fa-wallet"></i> MONTHLY BUDGET</h3>
      <button id="editBudget" class="btn-edit">
        <i class="fas fa-edit"></i> Edit
      </button>
    </div>
    <div class="budget-display">
      <div class="budget-progress">
        <div class="progress-labels">
          <span>Spent: <strong><span id="spentAmount">₱0.00</span></strong></span>
          <span>Remaining: <strong><span id="remainingAmount">₱0.00</span></strong></span>
        </div>
        <div class="progress-bar">
          <div id="budgetProgress" class="progress-fill" style="width: 0%"></div>
        </div>
        <div class="budget-total">
          Total Budget: <strong><span id="totalBudgetDisplay">₱0.00</span></strong>
        </div>
      </div>
    </div>
  </div>

  <div class="stats">
    <div class="stat-card">
      <i class="fas fa-utensils"></i>
      <div class="stat-value">₱2000</div>
      <div class="stat-label">Food</div>
    </div>
    <div class="stat-card">
      <i class="fas fa-car"></i>
      <div class="stat-value">₱1500</div>
      <div class="stat-label">Transportation</div>
    </div>
    <div class="stat-card">
      <i class="fas fa-film"></i>
      <div class="stat-value">₱800</div>
      <div class="stat-label">Entertainment</div>
    </div>
  </div>

  <div class="card recent-transactions">
    <h3><i class="fas fa-history"></i> RECENT SPENDING</h3>
    <div id="transactionsList">
      <div class="empty-state">
        <i class="fas fa-receipt"></i>
        <p>No transactions yet</p>
      </div>
    </div>
  </div>
</div>

<div id="notification" class="notification" style="display: none;"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Save Budget Button (Static Behavior)
  document.getElementById('saveBudget')?.addEventListener('click', function() {
    const amount = parseFloat(document.getElementById('monthlyBudget').value);

    if (!amount || amount <= 0) {
      showNotification('Please enter a valid amount', 'error');
      return;
    }

    // Simulating the budget save action
    document.getElementById('totalBudgetDisplay').textContent = '₱' + amount.toFixed(2);
    document.getElementById('spentAmount').textContent = '₱0.00';
    document.getElementById('remainingAmount').textContent = '₱' + amount.toFixed(2);
    document.getElementById('budgetProgress').style.width = '0%';

    // Hide the budget setting card after saving
    document.getElementById('budgetSetter').style.display = 'none';

    showNotification('Budget set successfully!', 'success');
  });

  // Edit Budget Button (Static Behavior)
  document.getElementById('editBudget')?.addEventListener('click', function() {
    if (confirm('Reset all budgets for this month?')) {
      // Resetting the budget to default values
      document.getElementById('totalBudgetDisplay').textContent = '₱0.00';
      document.getElementById('spentAmount').textContent = '₱0.00';
      document.getElementById('remainingAmount').textContent = '₱0.00';
      document.getElementById('budgetProgress').style.width = '0%';

      // Show the budget setting card again after resetting
      document.getElementById('budgetSetter').style.display = 'block';

      showNotification('Budget reset successfully!', 'success');
    }
  });

  function showNotification(message, type) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = 'notification ' + type;
    notification.style.display = 'block';
    setTimeout(() => {
      notification.style.display = 'none';
    }, 5000);
  }
});

</script>
@endsection
