@extends('layouts.main')

@section('content')
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/analysis.css', 'resources/js/chart.js'])
<div class="analysis-wrapper">
    <h1>Analysis Page</h1>
    <div class="summary-metrics">
        <p>Total Spending: ₱<span id="totalSpending">{{ number_format($totalSpending, 2) }}</span></p>
        <p>Daily Average: ₱<span id="dailyAverage">{{ number_format($dailyAverage, 2) }}</span></p>
    </div>

    <!-- Improved Date Range Selector -->
    <div class="date-range-container">
        <div class="date-range-selector">
            <h2>Select Date Range</h2>
            <div class="date-range-fields">
                <div class="form-group">
                    <label for="startDate">From:</label>
                    <input type="date" id="startDate" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label for="endDate">To:</label>
                    <input type="date" id="endDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <button id="applyDateRange" class="btn btn-primary">
                    <span class="btn-text">Apply</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="card-content">
                <h3>Total Spending</h3>
                <p class="amount">₱<span id="totalSpendingCard">{{ number_format($totalSpending, 2) }}</span></p>
                <p class="change">All time</p>
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
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="card-content">
                <h3>Budget Utilization</h3>
                <p class="amount"><span id="budgetUtilization">0</span>%</p>
                <p class="change">Current period</p>
            </div>
        </div>
    </div>

    <div class="chart-row">
        <!-- Spending Trend -->
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
            </div>
            <h4 id="spendingTitle" class="text-center mb-3">Monthly Spending</h4>
            <div class="chart-wrapper">
                <div class="chart-loading-overlay">
                    <div class="chart-spinner"></div>
                </div>
                <canvas id="spendingTrendChart"></canvas>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="chart-container">
            <h2>Category Breakdown</h2>
            <div class="chart-wrapper">
                <div class="chart-loading-overlay">
                    <div class="chart-spinner"></div>
                </div>
                <canvas id="categoryBreakdownChart"></canvas>
            </div>
        </div>
    </div>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable with buttons
    const breakdownTable = $('#categoryBreakdownTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[1, 'desc']], // Sort by spent amount by default
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
                legend: { position: 'top' }
            },
            scales: {
                x: { title: { display: true, text: 'Date' }},
                y: { 
                    title: { display: true, text: 'Amount (₱)' }, 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
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
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d',
                    '#17a2b8', '#6610f2', '#e83e8c', '#fd7e14', '#20c997'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
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
    function loadData(startDate, endDate, viewType = 'monthly') {
        setLoading(document.getElementById('applyDateRange'), true);
        document.querySelectorAll('.chart-container').forEach(container => {
            setChartLoading(container, true);
        });

        fetch(`/analysis/data/custom?start=${startDate}&end=${endDate}&view=${viewType}`)
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
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
                    document.getElementById('totalSpendingCard').textContent =
                        data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('dailyAverageCard').textContent =
                        data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        
                    if (data.totalAllocation > 0) {
                        const utilization = (data.totalSpending / data.totalAllocation * 100).toFixed(1);
                        document.getElementById('budgetUtilization').textContent = utilization;
                    }
                }

                // Update title
                const viewTypeText = viewType.charAt(0).toUpperCase() + viewType.slice(1);
                document.getElementById('spendingTitle').innerText =
                    `${viewTypeText} Spending from ${new Date(startDate).toLocaleDateString()} to ${new Date(endDate).toLocaleDateString()}`;
            })
            .catch(error => {
                console.error('Error loading data:', error);
                alert('Failed to load data. Please try again.');
            })
            .finally(() => {
                setLoading(document.getElementById('applyDateRange'), false);
                document.querySelectorAll('.chart-container').forEach(container => {
                    setChartLoading(container, false);
                });
            });
    }

    // Event listeners
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

    // Initial load
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    loadData(startDate, endDate);
});
</script>
@endsection