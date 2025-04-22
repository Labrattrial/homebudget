@extends('layouts.main')

@section('content')
<div class="welcome-card">
  <div>
    <h1>Welcome, {{ $user->name ?? 'User' }}!</h1>
    <p>Here's a quick overview of your finances</p>
  </div>
  <i class="fas fa-coins fa-3x"></i>
</div>

<div class="stats">
  <!-- NET WORTH CARD -->
  <div class="card" id="netWorthCard">
    <i class="fas fa-piggy-bank fa-2x" style="color:#4b8dbf;"></i>
    <h3>MY NET WORTH</h3>
    <div class="budget-wrapper">
      <input type="number" class="set-budget-input" id="budgetInput" placeholder="Set Budget" />
      <button class="set-budget-btn" id="setBudgetBtn">Set</button>
    </div>
    <div class="budget-amounts">
      <p class="amount">Spent: ₱<span id="totalExpenses">{{ number_format($totalExpenses, 2) }}</span></p>
      <p class="amount">Balance: ₱<span id="balance">{{ number_format($balance, 2) }}</span></p>
      <p class="amount" id="budgetMarker" style="display: none;">Budget: ₱<span id="budgetAmount"></span></p>
    </div>
    <small>Last 30 Days</small>
  </div>

  <!-- CATEGORY BREAKDOWN -->
<div class="card">
  <i class="fas fa-chart-pie fa-2x" style="color:#4b8dbf;"></i>
  <h3>CATEGORY BREAKDOWN</h3>
  <div style="display: flex; justify-content: center; align-items: center; gap: 2rem; flex-wrap: wrap;">
    <div class="fake-pie"></div>
    <div>
      @if(count($categoryData) > 0)
        <ul style="padding-left: 1rem; text-align: left;">
          @foreach($categoryData as $category)
            <li>{{ $category['name'] }}: ₱{{ number_format($category['total'], 2) }}</li>
          @endforeach
        </ul>
      @else
        <p>No category data available.</p>
      @endif
    </div>
  </div>
</div>


<!-- MONTHLY EXPENSES -->
<div class="card">
  <i class="fas fa-chart-bar fa-2x" style="color:#4b8dbf;"></i>
  <h3>MONTHLY EXPENSES</h3>
  <div class="fake-bar-graph">
    @if($totalExpenses > 0)
      @foreach($categoryData as $category)
        @php
          // Ensure height is a valid number
          $height = $totalExpenses > 0 ? round(($category['total'] / $totalExpenses) * 100, 2) : 0;
        @endphp
        <div class="bar-container">
        <div class="bar" style="height: '{{ is_numeric($height) ? $height : 0 }}%';"></div>
          <span class="bar-name">{{ $category['name'] }}</span>
        </div>
      @endforeach
    @else
      <p>No expenses recorded for this month.</p>
    @endif
  </div>
</div>



<div id="notification" class="notification" style="display: none;"></div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const setBudgetBtn = document.getElementById('setBudgetBtn');
    const budgetInput = document.getElementById('budgetInput');
    const budgetMarker = document.getElementById('budgetMarker');
    const budgetAmount = document.getElementById('budgetAmount');
    const totalExpenses = parseFloat(document.getElementById('totalExpenses').innerText.replace(',', ''));
    const notification = document.getElementById('notification');

    let budget = 0;

    setBudgetBtn.addEventListener('click', () => {
      const inputValue = parseFloat(budgetInput.value);
      if (isNaN(inputValue) || inputValue <= 0) {
        alert('Please enter a valid budget.');
        return;
      }

      budget = inputValue;
      budgetAmount.innerText = budget.toLocaleString();
      budgetMarker.style.display = 'block';
      setBudgetBtn.style.display = 'none';
      budgetInput.style.display = 'none';

      checkBudget(totalExpenses, budget);
    });

    const checkBudget = (expenses, budget) => {
      const percentage = (expenses / budget) * 100;
      if (percentage >= 85 && percentage < 100) {
        showNotification('warning', 'You are near your budget limit.');
      } else if (percentage >= 100) {
        showNotification('error', 'You have exceeded your budget!');
      } else {
        hideNotification();
      }
    };

    const showNotification = (type, message) => {
      notification.style.display = 'block';
      notification.className = 'notification ' + type;
      notification.innerText = message;
    };

    const hideNotification = () => {
      notification.style.display = 'none';
    };

    document.querySelectorAll('.bar').forEach(bar => {
      const height = bar.dataset.height;
      if (height) {
        bar.style.height = height + '%';
      }
    });
  });
</script>
@endsection
