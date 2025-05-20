@extends('layouts.main')

@section('content')
@vite(['resources/css/dashboard.css', 'resources/js/chart.js', 'resources/js/fontawesome.js', 'resources/js/app.js'])
<div class="dashboard-container">
  <!-- Welcome Section -->
  <div class="welcome-card gradient-bg">
    <div class="welcome-content">
      <h1>Welcome back, <span class="user-name">{{ Auth::user()->name }}</span></h1>
      <p class="welcome-subtitle">Here's your financial overview for <span class="current-month">{{ date('F Y') }}</span></p>
      <div class="quick-stats">
        <div class="quick-stat">
          <div class="stat-icon"><i class="fas fa-piggy-bank"></i></div>
          <div>
            <p class="stat-label">Monthly Budget</p>
            <p class="stat-value"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($budget, 2) }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="welcome-illustration">
      <i class="fas fa-chart-pie fa-4x"></i>
    </div>
  </div>

  <!-- Budget Setting Section -->
  <div id="budgetSetter" class="card budget-setter" style="{{ $budget > 0 ? 'display: none;' : '' }}">
    <div class="section-header">
      <h2><i class="fas fa-wallet"></i> Monthly Budget Setup</h2>
      <p>Set your financial goals for this month</p>
    </div>
    
    <div class="budget-input-container">
      <input type="hidden" id="currentMonth" value="{{ date('Y-m') }}">
      
      <div class="input-group">
          <label for="totalBudget">Total Monthly Budget</label>
          <div class="input-field">
          <span class="currency">{{ Auth::user()->currency_symbol }}</span>
              <input type="number" id="totalBudget" name="amount_limit" placeholder="e.g. 25,000" min="0" step="100" required>
          </div>
      </div>

      <div class="category-budget-setter">
        <h4>Category Budget Allocation</h4>
        <p class="subtitle">Set budget limits for each category</p>
        
        <div class="allocation-summary">
          <span>Total Allocated: <strong id="totalAllocated"><span class="currency">{{ Auth::user()->currency_symbol }}</span>0.00</strong></span>
          <span>Remaining: <strong id="remainingAllocation"><span class="currency">{{ Auth::user()->currency_symbol }}</span>0.00</strong></span>
        </div>

        <div class="category-allocation-list">
          @foreach($categoryAnalysis as $category)
          <div class="category-budget-item">
            <div class="category-budget-header">
            <div class="category-info">
              <div class="category-color" style="background-color: {{ $category['color'] }};"></div>
              <span class="category-name">{{ $category['name'] }}</span>
              </div>
              <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span><span class="category-allocation-display">0.00</span></span>
            </div>
            <div class="allocation-controls">
              <div class="allocation-input">
                <span class="currency">{{ Auth::user()->currency_symbol }}</span>
                <input type="number" 
                       class="category-allocation" 
                       data-category-id="{{ $category['id'] }}"
                       placeholder="0"
                       min="0"
                       step="100"
                       value="0">
              </div>
              <div class="allocation-slider">
                <input type="range" 
                       class="allocation-range" 
                       data-category-id="{{ $category['id'] }}"
                       min="0"
                       max="100"
                       step="1"
                       value="{{ $category['budget'] > 0 ? ($category['budget'] / $budget * 100) : 0 }}">
                <span class="percentage">0%</span>
              </div>
            </div>
            <div class="stat-progress">
              <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
              </div>
              <span class="stat-percent">0%</span>
            </div>
            <div class="stat-footer">
              <span class="stat-remaining"><span class="currency">{{ Auth::user()->currency_symbol }}</span>0.00 left</span>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      
      <div class="budget-actions">
        <button id="saveBudget" class="btn-primary">
          <i class="fas fa-save"></i> Set Budget
        </button>
        <button id="cancelBudget" class="btn-secondary">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- Dashboard Content -->
  <div id="budgetDashboard" class="dashboard-content" style="{{ $budget > 0 ? '' : 'display: none;' }}">
    <!-- First Row -->
    <div class="dashboard-row">
      <!-- Spending Trend Chart -->
      <div class="card chart-card">
        <div class="card-header">
          <h3><i class="fas fa-chart-bar"></i> Category Spending</h3>
        </div>
        <div class="chart-container">
          <canvas id="categorySpendingChart"></canvas>
        </div>
      </div>
      
      <!-- Budget Overview -->
      <div class="card budget-overview">
        <div class="card-header">
          <h3><i class="fas fa-wallet"></i> Budget Overview</h3>
          <button id="editBudget" class="btn-icon">
            <i class="fas fa-cog"></i>
          </button>
        </div>
        
        <div class="budget-display">
          <div class="stat-card">
            <div class="stat-header">
              <i class="fas fa-wallet"></i>
              <span class="stat-title">Total Budget</span>
            </div>
            <div class="stat-value" id="totalBudgetDisplay"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($budget, 2) }}</div>
            <div class="stat-progress">
              <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $budget > 0 ? min(100, (array_sum(array_column($categoryAnalysis, 'spent')) / $budget) * 100) : 0 }}%"></div>
              </div>
              <span class="stat-percent">
                @php
                  $totalSpent = array_sum(array_column($categoryAnalysis, 'spent'));
                  $percentage = $budget > 0 ? ($totalSpent / $budget) * 100 : 0;
                @endphp
                @if($budget > 0)
                  @if($percentage > 100)
                    100% ({{ round($percentage - 100, 1) }}% over)
                  @else
                    {{ round($percentage, 1) }}%
                  @endif
                @else
                  0%
                @endif
              </span>
            </div>
            <div class="stat-footer">
              @php
                $totalCategorySpent = array_sum(array_column($categoryAnalysis, 'spent'));
                $remaining = $budget - $totalCategorySpent;
              @endphp
              @if($budget > 0)
                @if($totalCategorySpent > $budget)
                  <span class="stat-warning"><i class="fas fa-exclamation-triangle"></i> Over budget</span>
                @else
                  <span class="stat-remaining"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($remaining, 2) }} left</span>
                @endif
              @else
                <span class="stat-remaining">No budget set</span>
              @endif
            </div>
          </div>
        </div>

        <!-- Category Budget Settings -->
        <div id="categoryBudgetSettings" class="category-budget-settings" style="display: none;">
          <h4>Category Budgets</h4>
          @foreach($categoryAnalysis as $category)
          <div class="category-budget-item">
            <div class="category-budget-header">
              <span class="category-name">{{ $category['name'] }}</span>
              <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['budget'], 2) }}</span>
            </div>
            <input type="range" 
                   class="category-budget-slider" 
                   data-category-id="{{ $category['id'] }}"
                   data-original-value="{{ $category['budget'] }}"
                   min="0" 
                   max="{{ max($budget, 10000) }}" 
                   step="100"
                   value="{{ $category['budget'] }}">
            <div class="stat-progress">
              <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $category['budget'] > 0 ? min(100, ($category['spent'] / $category['budget']) * 100) : 0 }}%"></div>
              </div>
              <span class="stat-percent">{{ $category['budget'] > 0 ? min(100, round(($category['spent'] / $category['budget']) * 100)) : 0 }}%</span>
            </div>
            <div class="stat-footer">
              @if($category['budget'] > 0)
                @if($category['remaining'] < 0)
                  <span class="stat-warning"><i class="fas fa-exclamation-triangle"></i> Over budget</span>
                @else
                  <span class="stat-remaining"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['remaining'], 2) }} left</span>
                @endif
              @else
                <span class="stat-remaining">No budget set</span>
              @endif
            </div>
          </div>
          @endforeach
          <div class="budget-actions">
            <button id="saveCategoryBudgets" class="btn-primary">
              <i class="fas fa-save"></i> Save Category Budgets
            </button>
            <button id="cancelCategoryBudgets" class="btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Second Row -->
    <div class="dashboard-row">
      <!-- Category Breakdown -->
      <div class="card chart-card">
        <div class="card-header">
          <h3><i class="fas fa-pie-chart"></i> Category Breakdown</h3>
          <div class="view-options">
            <button class="view-btn active" data-view="table"><i class="fas fa-table"></i></button>
            <button class="view-btn" data-view="chart"><i class="fas fa-chart-pie"></i></button>
          </div>
        </div>
        
        <div class="chart-view" id="chartView" style="display:none;">
          <div class="chart-container">
            <canvas id="categoryBreakdownChart"></canvas>
          </div>
          <div class="chart-legend" id="categoryChartLegend"></div>
        </div>
        
        <div class="category-list" id="categoryListView">
          @php
            $totalSpent = array_sum(array_column($categoryAnalysis, 'spent'));
          @endphp
          @foreach($categoryAnalysis as $category)
          <div class="category-item" data-category-id="{{ $category['id'] }}">
            <div class="category-color" style="background-color: {{ $category['color'] }};"></div>
            <span class="category-name">{{ $category['name'] }}</span>
            <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['spent'], 2) }}</span>
            <span class="category-percent">{{ $totalSpent > 0 ? round(($category['spent'] / $totalSpent) * 100) : 0 }}%</span>
          </div>
          @endforeach
          <div class="category-total">
            <span class="total-label">Total Spent</span>
            <span class="total-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($totalSpent, 2) }}</span>
          </div>
        </div>
      </div>
      
      <!-- Recent Transactions -->
      <div class="card transactions-card">
        <div class="card-header">
          <h3><i class="fas fa-history"></i> Recent Transactions</h3>
        </div>
        
        <div class="transactions-list">
          @forelse($transactions->take(5) as $transaction)
          <div class="transaction-item">
            <div class="transaction-icon">
              <i class="fas fa-{{ $transaction->category->icon ?? 'shopping-bag' }}"></i>
            </div>
            <div class="transaction-details">
              <div class="transaction-name">{{ $transaction->description }}</div>
              <div class="transaction-category">{{ $transaction->category->name ?? 'Uncategorized' }}</div>
            </div>
            <div class="transaction-amount {{ $transaction->amount < 0 ? 'negative' : 'positive' }}">
              {{ $transaction->amount < 0 ? '-' : '+' }}<span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format(abs($transaction->amount), 2) }}
            </div>
          </div>
          @empty
          <div class="empty-state">
            <i class="fas fa-exchange-alt"></i>
            <p>No transactions yet</p>
          </div>
          @endforelse
        </div>

        <div class="view-all">
          <a href="{{ route('user.expenses') }}">View all transactions <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
    
    <!-- Third Row - Quick Stats -->
    <div class="stats-grid">
      @foreach($categoryAnalysis as $category)
      <div class="stat-card" title="{{ $category['name'] }}">
        <div class="stat-header">
          <i class="fas fa-{{ $category['icon'] ?? 'shopping-bag' }}"></i>
          <span class="stat-title">{{ $category['name'] }}</span>
        </div>
        <div class="stat-value"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['spent'], 2) }}</div>
        <div class="stat-progress">
          <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $category['budget'] > 0 ? min(100, ($category['spent'] / $category['budget']) * 100) : 0 }}%"></div>
          </div>
          <span class="stat-percent">{{ $category['budget'] > 0 ? min(100, round(($category['spent'] / $category['budget']) * 100)) : 0 }}%</span>
        </div>
        <div class="stat-footer">
          @if($category['budget'] > 0)
            @if($category['remaining'] < 0)
              <span class="stat-warning"><i class="fas fa-exclamation-triangle"></i> Over budget</span>
            @else
              <span class="stat-remaining"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['remaining'], 2) }} left</span>
            @endif
          @else
            <span class="stat-remaining">No budget set</span>
          @endif
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Notification System -->
<div id="notification" class="notification" style="display: none;"></div>

<!-- Add Transaction Modal -->
<div id="transactionModal" class="modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="transactionModalTitle">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="transactionModalTitle">Add New Transaction</h3>
      <button class="modal-close" aria-label="Close modal">&times;</button>
    </div>
    <form id="transactionForm" action="{{ route('user.expenses.store') }}" method="POST">
      @csrf
      <div class="form-group">
        <label for="transactionDescription">Description</label>
        <input type="text" id="transactionDescription" name="description" required>
      </div>
      <div class="form-group">
        <label for="transactionAmount">Amount</label>
        <div class="input-field">
          <span class="currency">{{ Auth::user()->currency_symbol }}</span>
          <input type="number" id="transactionAmount" name="amount" step="0.01" required>
        </div>
      </div>
      <div class="form-group">
        <label for="transactionCategory">Category</label>
        <select id="transactionCategory" name="category_id" required>
          @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label for="transactionDate">Date</label>
        <input type="date" id="transactionDate" name="date" value="{{ date('Y-m-d') }}" required>
      </div>
      <button type="submit" class="btn-primary">Save Transaction</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal and other UI elements
    initModal();
    initTooltips();
    initBudgetControls();
    initCharts();
    initViewToggles();

    function initModal() {
        const modal = document.getElementById('transactionModal');
        const modalClose = document.querySelector('.modal-close');
        const addTransactionBtn = document.getElementById('addTransactionBtn');

        if (addTransactionBtn) {
            addTransactionBtn.addEventListener('click', () => modal.style.display = 'block');
        }
        if (modalClose) {
            modalClose.addEventListener('click', () => modal.style.display = 'none');
        }
        window.addEventListener('click', (event) => {
            if (event.target === modal) modal.style.display = 'none';
        });

        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', handleTransactionSubmit);
        }
    }

    function initTooltips() {
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                animation: 'scale',
                duration: 200,
                arrow: true
            });
        }
    }

    function initBudgetControls() {
        const saveBudgetBtn = document.getElementById('saveBudget');
        const cancelBudgetBtn = document.getElementById('cancelBudget');
        const editBudgetBtn = document.getElementById('editBudget');
        const totalBudgetInput = document.getElementById('totalBudget');
        const categoryAllocations = document.querySelectorAll('.category-allocation');
        const allocationRanges = document.querySelectorAll('.allocation-range');
        const budgetSetter = document.getElementById('budgetSetter');
        const budgetDashboard = document.getElementById('budgetDashboard');
        
        // Store original values
        let originalTotalBudget = totalBudgetInput.value;
        let originalAllocations = {};
        
        // Initialize original allocation values
        categoryAllocations.forEach(input => {
            originalAllocations[input.dataset.categoryId] = input.value;
        });

        if (editBudgetBtn) {
            editBudgetBtn.addEventListener('click', () => {
                // Show budget setter and hide dashboard
                budgetSetter.style.display = 'block';
                budgetDashboard.style.display = 'none';
                
                // Store current values for potential cancellation
                originalTotalBudget = totalBudgetInput.value;
                categoryAllocations.forEach(input => {
                    originalAllocations[input.dataset.categoryId] = input.value;
                });
                
                // Scroll to budget setter
                window.scrollTo({
                    top: budgetSetter.offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        }

        if (totalBudgetInput) {
            totalBudgetInput.addEventListener('input', updateAllocationLimits);
        }

        categoryAllocations.forEach(input => {
            input.addEventListener('input', function() {
                updateAllocationFromInput(this);
                updateAllocationSummary();
            });
        });

        allocationRanges.forEach(range => {
            range.addEventListener('input', function() {
                updateAllocationFromRange(this);
                updateAllocationSummary();
            });
        });

        function updateAllocationFromInput(input) {
            const categoryId = input.dataset.categoryId;
            const amount = Number(input.value);
            const totalBudget = Number(totalBudgetInput.value);
            const percentage = totalBudget > 0 ? Math.min(100, (amount / totalBudget * 100)) : 0;
            
            const range = document.querySelector(`.allocation-range[data-category-id="${categoryId}"]`);
            const percentageDisplay = range.parentElement.querySelector('.percentage');
            
            range.value = percentage;
            percentageDisplay.textContent = `${Math.round(percentage)}%`;
        }

        function updateAllocationFromRange(range) {
            const categoryId = range.dataset.categoryId;
            const percentage = Number(range.value);
            const totalBudget = Number(totalBudgetInput.value);
            const amount = (percentage / 100) * totalBudget;
            
            const input = document.querySelector(`.category-allocation[data-category-id="${categoryId}"]`);
            const percentageDisplay = range.parentElement.querySelector('.percentage');
            
            input.value = Math.round(amount);
            percentageDisplay.textContent = `${Math.round(percentage)}%`;
        }

        function updateAllocationLimits() {
            const totalBudget = Number(totalBudgetInput.value);
            allocationRanges.forEach(range => {
                range.max = 100;
            });
            updateAllocationSummary();
        }

        function updateAllocationSummary() {
            const totalBudget = Number(totalBudgetInput.value);
            const totalAllocated = Array.from(categoryAllocations)
                .reduce((sum, input) => sum + Number(input.value), 0);
            const remaining = totalBudget - totalAllocated;
            
            document.getElementById('totalAllocated').textContent = `<span class="currency">{{ Auth::user()->currency_symbol }}</span>${totalAllocated.toLocaleString()}`;
            document.getElementById('remainingAllocation').textContent = `<span class="currency">{{ Auth::user()->currency_symbol }}</span>${remaining.toLocaleString()}`;
            
            // Update visual feedback
            const remainingElement = document.getElementById('remainingAllocation');
            if (remaining < 0) {
                remainingElement.style.color = 'var(--danger-color)';
            } else {
                remainingElement.style.color = 'var(--text-primary)';
            }
        }

        if (cancelBudgetBtn) {
            cancelBudgetBtn.addEventListener('click', () => {
                // Restore original values
                totalBudgetInput.value = originalTotalBudget;
                categoryAllocations.forEach(input => {
                    const categoryId = input.dataset.categoryId;
                    input.value = originalAllocations[categoryId];
                    updateAllocationFromInput(input);
                });
                
                // Hide budget setter and show dashboard
                budgetSetter.style.display = 'none';
                budgetDashboard.style.display = 'block';
            });
        }

        if (saveBudgetBtn) {
            saveBudgetBtn.addEventListener('click', async function() {
                const totalBudget = Number(totalBudgetInput.value);
                const totalAllocated = Array.from(categoryAllocations)
                    .reduce((sum, input) => sum + Number(input.value), 0);
                
                if (totalAllocated > totalBudget) {
                    showNotification('Total category allocations cannot exceed total budget', 'error');
                    return;
                }

                const budgets = Array.from(categoryAllocations).map(input => ({
                    category_id: parseInt(input.dataset.categoryId),
                    amount_limit: Number(input.value)
                }));

                const requestData = {
                    month: document.getElementById('currentMonth').value,
                    amount_limit: totalBudget,
                    budgets: budgets
                };

                console.log('Sending budget data:', requestData); // Debug log

                try {
                    const response = await fetch("{{ route('saveBudgets') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(requestData)
                    });

                    const data = await response.json();
                    console.log('Server response:', data); // Debug log

                    if (data.success) {
                        showNotification('Budget and allocations saved successfully!', 'success');
                        // Hide budget setter and show dashboard
                        budgetSetter.style.display = 'none';
                        budgetDashboard.style.display = 'block';
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        showNotification(data.message || 'Error saving budgets', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while saving budgets', 'error');
                }
            });
        }
    }

    function initViewToggles() {
        const viewButtons = document.querySelectorAll('.view-btn');
        const chartView = document.getElementById('chartView');
        const categoryListView = document.getElementById('categoryListView');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                chartView.style.display = this.dataset.view === 'chart' ? 'block' : 'none';
                categoryListView.style.display = this.dataset.view === 'table' ? 'block' : 'none';
            });
        });
    }

    async function handleTransactionSubmit(e) {
        e.preventDefault();
        
        try {
            const response = await fetch(e.target.action, {
                method: 'POST',
                body: new FormData(e.target),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Transaction added successfully!', 'success');
                document.getElementById('transactionModal').style.display = 'none';
                e.target.reset();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message || 'Error adding transaction', 'error');
            }
        } catch (error) {
            showNotification('An error occurred. Please try again.', 'error');
            console.error('Error:', error);
        }
    }

    function initCharts() {
        // Category Spending Chart
        const spendingCtx = document.getElementById('categorySpendingChart')?.getContext('2d');
        if (spendingCtx) {
            const categoryNames = {!! json_encode(array_column($categoryData, 'name')) !!};
            const categoryTotals = {!! json_encode(array_column($categoryData, 'total')) !!};
            const categoryColors = {!! json_encode(array_column($categoryData, 'color')) !!};

            window.categorySpendingChart = new Chart(spendingCtx, {
                type: 'bar',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        label: 'Monthly Spending',
                        data: categoryTotals,
                        backgroundColor: categoryColors,
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `<span class="currency">{{ Auth::user()->currency_symbol }}</span>${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '{{ Auth::user()->currency_symbol }}' + (value / 1000) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Category Breakdown Chart
        const categoryCtx = document.getElementById('categoryBreakdownChart')?.getContext('2d');
        if (categoryCtx) {
            const categoryNames = {!! json_encode(array_column($categoryAnalysis, 'name')) !!};
            const categoryTotals = {!! json_encode(array_column($categoryAnalysis, 'spent')) !!};
            const categoryColors = {!! json_encode(array_column($categoryAnalysis, 'color')) !!};
            const totalSpent = categoryTotals.reduce((a, b) => a + b, 0);

            window.categoryBreakdownChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        data: categoryTotals,
                        backgroundColor: categoryColors,
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const percentage = Math.round((context.raw / totalSpent) * 100);
                                    return `${context.label}: <span class="currency">{{ Auth::user()->currency_symbol }}</span>${context.raw.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Create custom legend
            const legendContainer = document.getElementById('categoryChartLegend');
            if (legendContainer) {
                legendContainer.innerHTML = ''; // Clear existing legend
                categoryNames.forEach((name, index) => {
                    const percentage = Math.round((categoryTotals[index] / totalSpent) * 100);
                    const legendItem = document.createElement('div');
                    legendItem.className = 'legend-item';
                    legendItem.innerHTML = `
                        <span class="legend-color" style="background-color: ${categoryColors[index]}"></span>
                        <span class="legend-label">${name}</span>
                        <span class="legend-value"><span class="currency">{{ Auth::user()->currency_symbol }}</span>${categoryTotals[index].toLocaleString()} (${percentage}%)</span>
                    `;
                    legendContainer.appendChild(legendItem);
                });
            }
        }
    }

    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        if (!notification) return;
        
        notification.textContent = message;
        notification.className = 'notification ' + type;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
                notification.style.opacity = '1';
            }, 300);
        }, 4000);
    }
});
</script>
@endsection