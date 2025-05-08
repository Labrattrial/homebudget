@extends('layouts.main')

@section('content')
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/analysis.css', 'resources/js/chart.js'])

<div class="dashboard-container"> <!-- Match dashboard container for positioning -->
    <div class="analysis-wrapper">
        <!-- Preferences Bar -->
        <div class="preferences-bar">
            <div class="spending-visibility">
                <label class="switch" aria-label="Toggle spending visibility">
                    <input type="checkbox" id="spendingToggle" checked aria-checked="true">
                    <span class="slider round"></span>
                </label>
                <span class="preference-label">Show Spending Amounts</span>
            </div>
        </div>

        <!-- Achievement Banner -->
        <div id="achievementBanner" class="achievement-banner" style="display: none;">
            <div class="achievement-content">
                <span class="achievement-icon">🎉</span>
                <span class="achievement-message"></span>
            </div>
            <button class="close-banner" aria-label="Close achievement banner">&times;</button>
        </div>

        <h1>Financial Analysis</h1>
        
        <!-- Summary Metrics -->
        <div class="summary-metrics">
            <p>Total Spending: ₱<span id="totalSpending">{{ number_format($totalSpending, 2) }}</span></p>
            <p>Daily Average: ₱<span id="dailyAverage">{{ number_format($dailyAverage, 2) }}</span></p>
        </div>

        <!-- Date Range Selector -->
        <div class="date-range-container">
            <div class="date-range-selector">
                <div class="preset-ranges">
                    <button class="btn btn-outline-secondary range-preset" data-days="7">7 Days</button>
                    <button class="btn btn-outline-secondary range-preset" data-days="30">30 Days</button>
                    <button class="btn btn-outline-secondary range-preset" data-days="90">90 Days</button>
                    <button class="btn btn-outline-secondary range-preset" data-days="365">1 Year</button>
                </div>
                <div class="date-range-fields">
                    <div class="form-group">
                        <label for="startDate">From</label>
                        <input type="date" id="startDate" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label for="endDate">To</label>
                        <input type="date" id="endDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <button id="applyDateRange" class="btn btn-primary" aria-label="Apply date range">
                        <span class="btn-text">Apply</span>
                        <span class="spinner" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-content">
                    <h3>Budget Status</h3>
                    <div class="progress-container">
                        <div class="progress-bar" id="budgetProgressBar">
                            <div class="progress-fill" id="budgetProgressFill"></div>
                        </div>
                        <span class="progress-text" id="budgetProgressText"></span>
                    </div>
                    <p class="change">Current period</p>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <div class="card-content">
                    <h3>Potential Savings</h3>
                    <p class="amount">₱<span id="potentialSavings">0.00</span></ p>
                    <p class="change">Based on 10% reduction</p>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="card-content">
                    <h3>Daily Average</h3>
                    <p class="amount">₱<span id="dailyAverageCard">{{ number_format($dailyAverage, 2) }}</span></p>
                    <p class="change">Current period</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="chart-row">
            <!-- Spending Trend Chart -->
            <div class="chart-container">
                <h2>Spending Trend</h2>
                <div class="chart-tabs">
                    <button class="btn btn-secondary chart-filter-btn" id="dailyTab" data-type="daily">
                        <span class="btn-text">Daily</span>
                        <span class="spinner"></span>
                    </button>
                    <button class="btn btn-secondary chart-filter-btn" id="weeklyTab" data-type="weekly">
                        <span class="btn-text">Weekly</span>
                        <span class="spinner"></span>
                    </button>
                    <button class="btn btn-secondary chart-filter-btn active" id="monthlyTab" data-type="monthly">
                        <span class="btn-text">Monthly</span>
                        <span class="spinner"></span>
                    </button>
                    <div class="chart-actions">
                        <button class="btn btn-outline-secondary" onclick="exportChart('spendingTrendChart')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <h4 id="spendingTitle" class="text-center mb-3">Monthly Spending</h4>
                <div class="chart-wrapper">
                    <div class="chart-loading-overlay">
                        <div class="chart-spinner"></div>
                    </div>
                    <canvas id="spendingTrendChart"></canvas>
                </div>
            </div>

            <!-- Category Breakdown Chart -->
            <div class="chart-container">
                <h2>Category Breakdown</h2>
                <div class="chart-wrapper">
                    <div class="chart-loading-overlay">
                        <div class="chart-spinner"></div>
                    </div>
                    <canvas id="categoryBreakdownChart"></canvas>
                    <div class="chart-actions">
                        <button class="btn btn-outline-secondary" onclick="exportChart('categoryBreakdownChart')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown Table -->
        <div class="detailed-breakdown">
            <h2>Detailed Category Breakdown</h2>
            <table id="categoryBreakdownTable" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Spent Amount</th>
                        <th>Allocated Amount</th>
                        <th>Utilization</th>
                        <th>Percentage of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($categoryBreakdown))
                        @foreach($categoryBreakdown as $category)
                            <tr>
                                <td>{{ $category['name'] }}</td>
                                <td>₱{{ number_format($category['amount'], 2) }}</td>
                                <td>₱{{ number_format($category['allocated'] ?? 0, 2) }}</td>
                                <td>
                                    @if($category['allocated'] > 0)
                                        {{ round(($category['amount'] / $category['allocated']) * 100, 1) }}%
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    {{ $totalSpending > 0 ? round(($category['amount'] / $totalSpending) * 100, 1) . '%' : '0%' }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportChart(chartId) {
    const canvas = document.getElementById(chartId);
    const link = document.createElement('a');
    link.download = `${chartId}-${new Date().toISOString().split('T')[0]}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable with buttons
    const breakdownTable = $('#categoryBreakdownTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[1, 'desc']],
        language: {
            emptyTable: "No data available in table"
        }
    });

    // Initialize charts
    const trendCtx = document.getElementById('spendingTrendChart').getContext('2d');
    const categoryCtx = document.getElementById('categoryBreakdownChart').getContext('2d');

    const spendingTrendChart = new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: JSON.parse('@json($trendDates ?? [])'),
            datasets: [{
                label: 'Spending Trend',
                data: JSON.parse('@json($trendAmounts ?? [])'),
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw || 0;
                            return `₱${value.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        }
                    }
                }
            },
            scales: {
                x: { 
                    title: { display: true, text: 'Date' },
                    grid: { display: false }
                },
                y: { 
                    title: { display: true, text: 'Amount (₱)' }, 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    const categoryBreakdownChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: JSON.parse('@json($categoryNames ?? [])'),
            datasets: [{
                data: JSON.parse('@json($categoryAmounts ?? [])'),
                backgroundColor: [
                    '#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F',
                    '#EDC948', '#B07AA1', '#FF9DA7', '#9C755F', '#BAB0AC'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ₱${value.toLocaleString('en-PH', { minimumFractionDigits: 2 })} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Loading state functions
    function setLoading(button, isLoading) {
        const spinner = button.querySelector('.spinner');
        const btnText = button.querySelector('.btn-text');
        
        if (isLoading) {
            button.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.style.opacity = '0.7';
            button.classList.add('button-loading');
        } else {
            button.disabled = false;
            spinner.style.display = 'none';
            btnText.style.opacity = '1';
            button.classList.remove('button-loading');
        }
    }

    function setChartLoading(chartContainer, isLoading) {
        const wrapper = chartContainer.querySelector('.chart-wrapper');
        const canvas = chartContainer.querySelector('canvas');
        
        if (isLoading) {
            wrapper.classList.add('chart-loading');
            canvas.style.visibility = 'hidden';
        } else {
            wrapper.classList.remove('chart-loading');
            canvas.style.visibility = 'visible';
        }
    }

    // Main data loading function
    function loadData(startDate, endDate, viewType = 'monthly', retryCount = 0) {
        const MAX_RETRIES = 3;
        const RETRY_DELAY = 2000; // 2 seconds

        setLoading(document.getElementById('applyDateRange'), true);
        document.querySelectorAll('.chart-container').forEach(container => {
            setChartLoading(container, true);
        });

        // Remove any existing error messages
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        fetch(`/analysis/data/custom?start=${startDate}&end=${endDate}&view=${viewType}`)
            .then(async res => {
                if (!res.ok) {
                    const errorData = await res.json();
                    throw new Error(errorData.message || 'Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                // Update charts
                if (data.trendDates && data.trendAmounts) {
                    spendingTrendChart.data.labels = data.trendDates;
                    spendingTrendChart.data.datasets[0].data = data.trendAmounts;
                    spendingTrendChart.update();
                }

                if (data.categoryNames && data.categoryAmounts) {
                    categoryBreakdownChart.data.labels = data.categoryNames;
                    categoryBreakdownChart.data.datasets[0].data = data.categoryAmounts;
                    categoryBreakdownChart.update();
                }

                // Update DataTable
                breakdownTable.clear();
                if (data.categoryNames?.length) {
                    const totalSpending = data.categoryAmounts.reduce((sum, amount) => sum + amount, 0);
                    const totalAllocation = data.categoryAllocations?.reduce((sum, amount) => sum + amount, 0) || 0;
                    
                    data.categoryNames.forEach((name, index) => {
                        const spent = data.categoryAmounts[index];
                        const allocated = data.categoryAllocations?.[index] || 0;
                        const utilization = allocated > 0 ? (spent / allocated * 100).toFixed(1) + '%' : 'N/A';
                        const percentage = totalSpending > 0 ? (spent / totalSpending * 100).toFixed(1) + '%' : '0%';
                        
                        breakdownTable.row.add([
                            name,
                            `₱${spent.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
                            `₱${allocated.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
                            utilization,
                            percentage
                        ]);
                    });
                }
                breakdownTable.draw();

                // Update metrics
                if (data.totalSpending !== undefined) {
                    document.getElementById('totalSpending').textContent =
                        data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('dailyAverage').textContent =
                        data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('dailyAverageCard').textContent =
                        data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        
                    if (data.totalAllocation > 0) {
                        const utilization = (data.totalSpending / data.totalAllocation * 100).toFixed(1);
                        document.getElementById('budgetProgressFill').style.width = `${Math.min(100, utilization)}%`;
                        document.getElementById('budgetProgressText').textContent = `${utilization}% of budget used`;
                        
                        // Set progress bar color
                        const progressFill = document.getElementById('budgetProgressFill');
                        if (utilization > 90) {
                            progressFill.style.backgroundColor = '#dc3545';
                        } else if (utilization > 70) {
                            progressFill.style.backgroundColor = '#ffc107';
                        } else {
                            progressFill.style.backgroundColor = '#28a745';
                        }
                    }
                    
                    // Calculate potential savings
                    const potentialSavings = data.totalSpending * 0.1;
                    document.getElementById('potentialSavings').textContent = 
                        potentialSavings.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                }

                // Update title
                const viewTypeText = viewType.charAt(0).toUpperCase() + viewType.slice(1);
                document.getElementById('spendingTitle').textContent =
                    `${viewTypeText} Spending from ${new Date(startDate).toLocaleDateString()} to ${new Date(endDate).toLocaleDateString()}`;
                
                // Check for achievements
                checkAchievements(data);
            })
            .catch(error => {
                console.error('Error loading data:', error);
                
                if (retryCount < MAX_RETRIES) {
                    // Retry after delay
                    setTimeout(() => {
                        loadData(startDate, endDate, viewType, retryCount + 1);
                    }, RETRY_DELAY);
                    return;
                }

                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.innerHTML = `
                    <div class="error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h4>Oops! Something went wrong</h4>
                            <p>${error.message || 'We couldn\'t load your data. Please check your connection and try again.'}</p>
                            <div class="error-actions">
                                <button class="btn btn-primary retry-btn">
                                    <i class="fas fa-sync-alt"></i> Try Again
                                </button>
                                <button class="btn btn-outline-secondary refresh-btn">
                                    <i class="fas fa-redo"></i> Refresh Page
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                const wrapper = document.querySelector('.analysis-wrapper');
                wrapper.insertBefore(errorMessage, wrapper.firstChild);
                
                errorMessage.querySelector('.retry-btn').addEventListener('click', function() {
                    errorMessage.remove();
                    loadData(startDate, endDate, viewType);
                });

                errorMessage.querySelector('.refresh-btn').addEventListener('click', function() {
                    window.location.reload();
                });
            })
            .finally(() => {
                setLoading(document.getElementById('applyDateRange'), false);
                document.querySelectorAll('.chart-container').forEach(container => {
                    setChartLoading(container, false);
                });
            });
    }

    // Achievement checking function
    function checkAchievements(data) {
        const banner = document.getElementById('achievementBanner');
        const message = document.querySelector('.achievement-message');
        
        // Hide banner initially
        banner.style.display = 'none';
        
        // Check for achievements
        if (data.totalAllocation > 0 && data.totalSpending < (data.totalAllocation * 0.9)) {
            message.textContent = `Great job! You've stayed under budget by ₱${(data.totalAllocation - data.totalSpending).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            banner.style.display = 'flex';
        } else if (data.totalAllocation > 0 && data.dailyAverage < (data.totalAllocation / 30 * 0.8)) {
            message.textContent = `Awesome! Your daily spending is 20% below average!`;
            banner.style.display = 'flex';
        }
        
        // Close banner event
        document.querySelector('.close-banner').addEventListener('click', function() {
            banner.style.display = 'none';
        });
    }

    // Add preset range functionality
    document.querySelectorAll('.range-preset').forEach(button => {
        button.addEventListener('click', function() {
            const days = parseInt(this.getAttribute('data-days'));
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - days);
            
            document.getElementById('startDate').valueAsDate = startDate;
            document.getElementById('endDate').valueAsDate = endDate;
            
            loadData(
                startDate.toISOString().split('T')[0],
                endDate.toISOString().split('T')[0]
            );
        });
    });

    // Apply date range event
    document.getElementById('applyDateRange').addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffInDays = (end - start) / (1000 * 60 * 60 * 24);

        if (diffInDays > 365) {
            alert('Date range cannot exceed 1 year');
            return;
        }

        loadData(startDate, endDate);
    });

    // Chart filter buttons
    document.querySelectorAll('.chart-filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.chart-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            const viewType = this.getAttribute('data-type');
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            loadData(startDate, endDate, viewType);
        });
    });

    // Spending visibility toggle
    document.getElementById('spendingToggle').addEventListener('change', function() {
        document.querySelector('.analysis-wrapper').classList.toggle('hide-spending', !this.checked);
        localStorage.setItem('spendingVisibility', this.checked);
    });

    // Load saved preference on page load
    const spendingToggle = document.getElementById('spendingToggle');
    const savedVisibility = localStorage.getItem('spendingVisibility');
    if (savedVisibility !== null) {
        spendingToggle.checked = savedVisibility === 'true';
        document.querySelector('.analysis-wrapper').classList.toggle('hide-spending', !spendingToggle.checked);
    }

    // Initial load
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    loadData(startDate, endDate);
});
</script>
@endsection

