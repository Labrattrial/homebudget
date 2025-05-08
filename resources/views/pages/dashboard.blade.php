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
        <div class="quick-stat">
          <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
          <div>
            <p class="stat-label">Spending Trend</p>
            <p class="stat-value trend-{{ $spendingTrendPercentage > 100 ? 'up' : 'down' }}">
              {{ abs(100 - $spendingTrendPercentage) }}% {{ $spendingTrendPercentage > 100 ? 'higher' : 'lower' }}
            </p>
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

      <div class="category-budget-allocation">
          <h3>Category Budget Allocation</h3>
          <p class="allocation-subtitle">Distribute your budget across categories</p>
          
          <div class="allocation-summary">
              <div class="total-allocated">
                  <span>Total Allocated:</span>
                  <span id="totalAllocated"><span class="currency">{{ Auth::user()->currency_symbol }}</span>0.00</span>
              </div>
              <div class="remaining-budget">
                  <span>Remaining:</span>
                  <span id="remainingBudget"><span class="currency">{{ Auth::user()->currency_symbol }}</span>0.00</span>
              </div>
          </div>

          <div class="category-allocations">
              @foreach($categories as $category)
              <div class="category-allocation-item">
                  <div class="category-info">
                      <i class="fas fa-{{ $category->icon ?? 'shopping-bag' }}"></i>
                      <span>{{ $category->name }}</span>
                  </div>
                  <div class="allocation-input">
                      <span class="currency">{{ Auth::user()->currency_symbol }}</span>
                      <input type="number" 
                             class="category-budget" 
                             data-category-id="{{ $category->id }}"
                             placeholder="0.00" 
                             min="0" 
                             step="100">
                      <span class="percentage">0%</span>
                  </div>
              </div>
              @endforeach
          </div>
      </div>
      
      <button id="saveBudget" class="btn-primary">
        <i class="fas fa-save"></i> Set Budget
      </button>
    </div>
  </div>

  <!-- Dashboard Content -->
  <div id="budgetDashboard" class="dashboard-content" style="{{ $budget > 0 ? '' : 'display: none;' }}">
    <!-- First Row -->
    <div class="dashboard-row">
      <!-- Spending Trend Chart -->
      <div class="card chart-card">
        <div class="card-header">
          <h3><i class="fas fa-chart-line"></i> Spending Trend</h3>
          <div class="time-filter">
            <button class="time-btn active" data-period="1M">1M</button>
            <button class="time-btn" data-period="3M">3M</button>
            <button class="time-btn" data-period="6M">6M</button>
            <button class="time-btn" data-period="1Y">1Y</button>
          </div>
        </div>
        <canvas id="spendingTrendChart"></canvas>
        <div class="chart-footer">
          <div class="trend-indicator">
            <i class="fas fa-arrow-{{ $spendingTrendPercentage > 100 ? 'up trend-up' : 'down trend-down' }}"></i>
            <span>{{ abs(100 - $spendingTrendPercentage) }}% {{ $spendingTrendPercentage > 100 ? 'higher' : 'lower' }} than last month</span>
          </div>
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
          <div class="budget-progress {{ ($budget > 0 && ($totalExpenses / $budget) > 0.8) ? 'warning' : '' }}">
            <div class="progress-labels">
              <span>Spent: <strong><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($totalExpenses, 2) }}</strong></span>
              <span>Remaining: <strong><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format(max($budget - $totalExpenses, 0), 2) }}</strong></span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: {{ $budget > 0 ? min(100, ($totalExpenses / $budget) * 100) : 0 }}%"></div>
              <div class="progress-threshold" style="left: 80%"></div>
            </div>
            <div class="budget-meta">
              <span>Budget: <strong><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($budget, 2) }}</strong></span>
              @if($budget > 0 && ($totalExpenses / $budget) > 0.8)
                <span class="warning-text"><i class="fas fa-exclamation-circle"></i> Close to limit</span>
              @endif
            </div>
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
          <canvas id="categoryBreakdownChart"></canvas>
        </div>
        
        <div class="category-list" id="categoryListView">
          @foreach($categoryAnalysis as $category)
          <div class="category-item">
            <div class="category-color" style="background-color: {{ $category['color'] }};"></div>
            <span class="category-name">{{ $category['name'] }}</span>
            <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($category['spent'], 2) }}</span>
            <span class="category-percent">{{ $budget > 0 ? round(($category['spent'] / $budget) * 100) : 0 }}%</span>
          </div>
          @endforeach
        </div>
      </div>
      
      <!-- Recent Transactions -->
      <div class="card transactions-card">
        <div class="card-header">
          <h3><i class="fas fa-history"></i> Recent Transactions</h3>
          <button class="btn-icon" id="addTransactionBtn">
            <i class="fas fa-plus"></i>
          </button>
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
          <span class="stat-percent">{{ $category['budget'] > 0 ? round(($category['spent'] / $category['budget']) * 100) : 0 }}%</span>
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
        const totalBudgetInput = document.getElementById('totalBudget');
        const categoryBudgetInputs = document.querySelectorAll('.category-budget');
        
        if (saveBudgetBtn) {
            saveBudgetBtn.addEventListener('click', handleSaveBudget);
        }

        // Add event listeners for total budget input
        if (totalBudgetInput) {
            totalBudgetInput.addEventListener('input', updateBudgetAllocation);
        }

        // Add event listeners for category budget inputs
        categoryBudgetInputs.forEach(input => {
            input.addEventListener('input', updateBudgetAllocation);
        });

        const editBudgetBtn = document.getElementById('editBudget');
        if (editBudgetBtn) {
            editBudgetBtn.addEventListener('click', () => {
                document.getElementById('budgetSetter').style.display = 'block';
                window.scrollTo({
                    top: document.getElementById('budgetSetter').offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        }
    }

    function updateBudgetAllocation() {
        const totalBudget = parseFloat(document.getElementById('totalBudget').value) || 0;
        const categoryInputs = document.querySelectorAll('.category-budget');
        let totalAllocated = 0;

        // Update each category's percentage and calculate total allocated
        categoryInputs.forEach(input => {
            const amount = parseFloat(input.value) || 0;
            const percentage = totalBudget > 0 ? (amount / totalBudget) * 100 : 0;
            input.nextElementSibling.textContent = `${percentage.toFixed(1)}%`;
            totalAllocated += amount;
        });

        // Update summary
        document.getElementById('totalAllocated').textContent = `₱${totalAllocated.toFixed(2)}`;
        document.getElementById('remainingBudget').textContent = `₱${(totalBudget - totalAllocated).toFixed(2)}`;

        // Visual feedback for over-allocation
        const remainingBudget = totalBudget - totalAllocated;
        const remainingElement = document.getElementById('remainingBudget');
        if (remainingBudget < 0) {
            remainingElement.style.color = 'var(--danger-color)';
        } else {
            remainingElement.style.color = '';
        }
    }

    async function handleSaveBudget(event) {
        event.preventDefault();
        
        const month = document.getElementById('currentMonth').value;
        const totalBudget = Number(document.getElementById('totalBudget').value);
        const categoryInputs = document.querySelectorAll('.category-budget');
        
        // Validate the budget input
        if (isNaN(totalBudget) || totalBudget <= 0) {
            showNotification('Please enter a valid budget amount greater than 0', 'error');
            return;
        }

        // Collect category budgets
        const categoryBudgets = Array.from(categoryInputs).map(input => ({
            category_id: input.dataset.categoryId,
            amount_limit: Number(input.value) || 0
        }));

        // Calculate total allocated
        const totalAllocated = categoryBudgets.reduce((sum, cat) => sum + cat.amount_limit, 0);
        
        // Validate total allocation
        if (totalAllocated > totalBudget) {
            showNotification('Total category allocation cannot exceed the total budget', 'error');
            return;
        }

        try {
            // Save overall budget
            const overallBudgetResponse = await fetch("{{ route('saveBudgets') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    month: month,
                    amount_limit: totalBudget,
                    category_id: null
                })
            });

            if (!overallBudgetResponse.ok) {
                throw new Error('Failed to save overall budget');
            }

            // Save category budgets
            for (const categoryBudget of categoryBudgets) {
                if (categoryBudget.amount_limit > 0) {
                    const categoryResponse = await fetch("{{ route('saveBudgets') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            month: month,
                            amount_limit: categoryBudget.amount_limit,
                            category_id: categoryBudget.category_id
                        })
                    });

                    if (!categoryResponse.ok) {
                        throw new Error('Failed to save category budget');
                    }
                }
            }

            showNotification('Budget saved successfully!', 'success');
            document.getElementById('budgetSetter').style.display = 'none';
            document.getElementById('budgetDashboard').style.display = 'block';
            
            // Reload data dynamically
            await loadDashboardData();
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message || 'An error occurred. Please try again.', 'error');
        }
    }

    // Function to load updated dashboard data
    async function loadDashboardData() {
        try {
            const response = await fetch("{{ route('user.dashboard.data') }}", {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load dashboard data');
            }

            const data = await response.json();
            updateDashboard(data);
            
            // Update charts
            if (window.spendingTrendChart) {
                updateSpendingTrendChart(data.spendingTrend);
            }
            updateCategoryBreakdownChart();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            showNotification('Failed to load updated data', 'error');
        }
    }

    // Function to update the dashboard with new data
    function updateDashboard(data) {
        // Update budget display
        document.querySelector('.stat-value').textContent = `₱${data.budget.toFixed(2)}`;
        
        // Update total expenses
        document.querySelector('.budget-progress .progress-fill').style.width = `${(data.totalExpenses / data.budget) * 100}%`;
        document.querySelector('.budget-progress .progress-labels span:first-child strong').textContent = `₱${data.totalExpenses.toFixed(2)}`;
        document.querySelector('.budget-progress .progress-labels span:last-child strong').textContent = `₱${(data.budget - data.totalExpenses).toFixed(2)}`;

        // Update category analysis
        const categoryListView = document.getElementById('categoryListView');
        categoryListView.innerHTML = ''; // Clear existing categories

        data.categoryAnalysis.forEach(category => {
            const categoryItem = document.createElement('div');
            categoryItem.className = 'category-item';
            categoryItem.innerHTML = `
                <div class="category-color" style="background-color: ${category.color};"></div>
                <span class="category-name">${category.name}</span>
                <span class="category-amount">₱${category.spent.toFixed(2)}</span>
                <span class="category-percent">${data.budget > 0 ? Math.round((category.spent / data.budget) * 100) : 0}%</span>
            `;
            categoryListView.appendChild(categoryItem);
        });

        // Update charts if necessary
        // You can add more updates here based on the structure of your data
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
        // Spending Trend Chart
        const trendCtx = document.getElementById('spendingTrendChart')?.getContext('2d');
        if (trendCtx) {
            window.spendingTrendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($spendingTrendLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May']) !!},
                    datasets: [{
                        label: 'Monthly Spending',
                        data: {!! json_encode($spendingTrendData ?? [22000, 19500, 21000, 23000, 18000]) !!},
                        backgroundColor: 'rgba(78, 121, 167, 0.1)',
                        borderColor: 'rgba(78, 121, 167, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(78, 121, 167, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: getChartOptions('line')
            });
        }

        // Category Breakdown Chart
        const categoryCtx = document.getElementById('categoryBreakdownChart')?.getContext('2d');
if (categoryCtx) {
    // Extract names, totals, and colors from the categoryData array
    const categoryNames = {!! json_encode(array_column($categoryData, 'name')) !!};
    const categoryTotals = {!! json_encode(array_column($categoryData, 'total')) !!};
    const categoryColors = {!! json_encode(array_column($categoryData, 'color')) !!};

    window.categoryBreakdownChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryNames, // Use the extracted names
            datasets: [{
                data: categoryTotals, // Use the extracted totals
                backgroundColor: categoryColors, // Use the extracted colors
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: getChartOptions('doughnut')
    });
}



        // Initialize time filter buttons
        document.querySelectorAll('.time-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                try {
                    const response = await fetch(`/api/spending-trend?period=${this.dataset.period}`);
                    const data = await response.json();
                    
                    if (data.success && window.spendingTrendChart) {
                        window.spendingTrendChart.data.labels = data.labels;
                        window.spendingTrendChart.data.datasets[0].data = data.values;
                        window.spendingTrendChart.update();
                        
                        updateTrendIndicator(data.trend);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Failed to load data', 'error');
                }
            });
        });
    }

    function getChartOptions(type) {
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#333',
                    titleFont: { size: 14 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    displayColors: type === 'doughnut',
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            if (type === 'doughnut') {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                            }
                            return `${label}: ₱${value.toLocaleString()}`;
                        }
                    }
                }
            }
        };

        if (type === 'line') {
            commonOptions.scales = {
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) {
                            return '₱' + (value / 1000) + 'k';
                        }
                    }
                },
                x: { grid: { display: false } }
            };
        }

        return commonOptions;
    }

    function updateTrendIndicator(trend) {
        const trendIndicator = document.querySelector('.trend-indicator');
        if (!trendIndicator) return;
        
        const trendIcon = trendIndicator.querySelector('i');
        const trendText = trendIndicator.querySelector('span');
        
        if (trend > 0) {
            trendIcon.className = 'fas fa-arrow-up trend-up';
            trendText.textContent = `${Math.abs(trend)}% higher than previous period`;
        } else {
            trendIcon.className = 'fas fa-arrow-down trend-down';
            trendText.textContent = `${Math.abs(trend)}% lower than previous period`;
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

    function initViewToggles() {
        const viewButtons = document.querySelectorAll('.view-btn');
        const chartView = document.getElementById('chartView');
        const categoryListView = document.getElementById('categoryListView');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                if (this.dataset.view === 'chart') {
                    chartView.style.display = 'block';
                    categoryListView.style.display = 'none';
                    // Update the chart with current data
                    updateCategoryBreakdownChart();
                } else {
                    chartView.style.display = 'none';
                    categoryListView.style.display = 'block';
                }
            });
        });
    }

    function updateCategoryBreakdownChart() {
        const categoryCtx = document.getElementById('categoryBreakdownChart')?.getContext('2d');
        if (!categoryCtx) return;

        // Get current category data from the list
        const categoryItems = document.querySelectorAll('.category-item');
        const categoryData = Array.from(categoryItems).map(item => ({
            name: item.querySelector('.category-name').textContent,
            total: parseFloat(item.querySelector('.category-amount').textContent.replace('₱', '').replace(/,/g, '')),
            color: item.querySelector('.category-color').style.backgroundColor
        }));

        // Destroy existing chart if it exists
        if (window.categoryBreakdownChart) {
            window.categoryBreakdownChart.destroy();
        }

        // Create new chart
        window.categoryBreakdownChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(cat => cat.name),
                datasets: [{
                    data: categoryData.map(cat => cat.total),
                    backgroundColor: categoryData.map(cat => cat.color),
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: getChartOptions('doughnut')
        });
    }

    // Add function to update spending trend chart
    function updateSpendingTrendChart(data) {
        if (!window.spendingTrendChart || !data) return;

        window.spendingTrendChart.data.labels = data.labels;
        window.spendingTrendChart.data.datasets[0].data = data.values;
        window.spendingTrendChart.update();
    }
});
</script>
@endsection