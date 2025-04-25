@extends('layouts.main')

@section('content')
@vite(['resources/css/analysis.css', 'resources/js/chart.js'])
<div class="analysis-wrapper">
    <h1>Analysis Page</h1>
    <div class="summary-metrics">
        <p>Total Spending: ₱<span id="totalSpending">{{ number_format($totalSpending, 2) }}</span></p>
        <p>Daily Average: ₱<span id="dailyAverage">{{ number_format($dailyAverage, 2) }}</span></p>
    </div>

    <!-- Custom Date Range Selector -->
    <div class="date-range-selector">
        <h2>Select Date Range</h2>
        <div class="form-group">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <button id="applyDateRange" class="btn btn-primary">
            <span class="btn-text">Apply</span>
            <span class="spinner"></span>
        </button>
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
    </div>

    <div class="chart-row">
        <!-- Spending Trend -->
        <div class="chart-container">
            <h2>Spending Trend</h2>
            <div class="chart-tabs">
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
        <h2>Category Breakdown</h2>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody id="categoryBreakdownTable">
                @if(!empty($categoryBreakdown))
                    @foreach($categoryBreakdown as $category)
                        <tr>
                            <td>{{ $category['name'] }}</td>
                            <td>₱{{ number_format($category['amount'], 2) }}</td>
                            <td>{{ round(($category['amount'] / $totalSpending) * 100, 1) }}%</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3">No data available</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Parsed PHP data into JS
        const trendDates = JSON.parse('@json($trendDates ?? [])');
        const trendAmounts = JSON.parse('@json($trendAmounts ?? [])');
        const categoryNames = JSON.parse('@json($categoryNames ?? [])');
        const categoryAmounts = JSON.parse('@json($categoryAmounts ?? [])');

        const fallbackDates = ['No Data'];
        const fallbackAmounts = [0];

        const trendCtx = document.getElementById('spendingTrendChart').getContext('2d');
        const categoryCtx = document.getElementById('categoryBreakdownChart').getContext('2d');

        // Initialize charts with responsive settings
        const spendingTrendChart = new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendDates.length ? trendDates : fallbackDates,
                datasets: [{
                    label: 'Spending Trend',
                    data: trendAmounts.length ? trendAmounts : fallbackAmounts,
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
                    y: { title: { display: true, text: 'Amount (₱)' }, beginAtZero: true }
                }
            }
        });

        const categoryBreakdownChart = new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: categoryNames.length ? categoryNames : ['No Data'],
                datasets: [{
                    data: categoryAmounts.length ? categoryAmounts : [1],
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
                    legend: { position: 'bottom' }
                }
            }
        });

        // Function to set button loading state
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

        // Function to set chart loading state
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

        // Weekly and Monthly tab switchers
document.querySelectorAll('.chart-filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        if (btn.classList.contains('button-loading')) return;

        // Set loading state
        setLoading(btn, true);
        const chartContainer = btn.closest('.chart-container');
        setChartLoading(chartContainer, true);

        const type = btn.getAttribute('data-type');

        // Update active button styles
        document.querySelectorAll('.chart-filter-btn').forEach(button => {
            button.classList.remove('active');
        });
        btn.classList.add('active');

        // Fetch data based on selected filter
        fetch(`/analysis/data/${type}`)
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (type === 'monthly') {
                    // Reset the chart data for monthly
                    resetChartData(spendingTrendChart, 'Monthly');

                    // Update monthly chart with daily data
                    spendingTrendChart.data.labels = data.monthlyDates; // Use dates for the month
                    spendingTrendChart.data.datasets[0].data = data.monthlyAmounts; // Use daily amounts for the month
                    spendingTrendChart.update();

                    // Update the title
                    document.getElementById('spendingTitle').innerText = `${type.charAt(0).toUpperCase() + type.slice(1)} Spending`;

                } else if (type === 'weekly') {
                    // Reset the chart data for weekly
                    resetChartData(spendingTrendChart, 'Weekly');

                    // Update weekly chart with weekly data
                    spendingTrendChart.data.labels = data.weeklyDates; // Use weeks instead of daily dates
                    spendingTrendChart.data.datasets[0].data = data.weeklyAmounts; // Use weekly amounts
                    spendingTrendChart.update();

                    // Update the title
                    document.getElementById('spendingTitle').innerText = `${type.charAt(0).toUpperCase() + type.slice(1)} Spending`;
                }

                // Update category breakdown if available
                if (data.categoryNames && data.categoryAmounts) {
                    categoryBreakdownChart.data.labels = data.categoryNames;
                    categoryBreakdownChart.data.datasets[0].data = data.categoryAmounts;
                    categoryBreakdownChart.update();

                    // Update table
                    const tableBody = document.getElementById('categoryBreakdownTable');
                    tableBody.innerHTML = '';
                    
                    if (data.categoryNames.length) {
                        const total = data.categoryAmounts.reduce((sum, amount) => sum + amount, 0);
                        data.categoryNames.forEach((name, index) => {
                            const amount = data.categoryAmounts[index];
                            const percentage = total > 0 ? (amount / total * 100).toFixed(1) : 0;
                            
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${name}</td>
                                <td>₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                <td>${percentage}%</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="3">No data available</td>';
                        tableBody.appendChild(row);
                    }
                }

                // Update summary metrics if available
                if (data.totalSpending) {
                    document.getElementById('totalSpending').textContent = 
                        data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('dailyAverage').textContent = 
                        data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('totalSpendingCard').textContent = 
                        data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('dailyAverageCard').textContent = 
                        data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                }
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                alert('Failed to load chart data. Please try again.');
            })
            .finally(() => {
                setLoading(btn, false);
                setChartLoading(chartContainer, false);
            });
    });
});

// Helper function to reset chart data based on view type
function resetChartData(chart, viewType) {
    // Clear chart data and labels to reset before switching tabs
    chart.data.labels = [];
    chart.data.datasets[0].data = [];
    
    // Adjust the Y-axis scale or other properties based on view type (Monthly or Weekly)
    if (viewType === 'Monthly') {
        chart.options.scales.y.min = 0; // Example for resetting the min scale (for daily data)
        chart.options.scales.y.max = 100; // Example for adjusting max scale
    } else if (viewType === 'Weekly') {
        chart.options.scales.y.min = 0; // Resetting for weekly data, if necessary
        chart.options.scales.y.max = 100; // Example for max scale
    }

    // Update chart
    chart.update();
}

        // Date range apply button
        const applyDateRangeBtn = document.getElementById('applyDateRange');
        applyDateRangeBtn.addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            // Check if both dates are selected
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            // Set the button and chart loading states
            setLoading(applyDateRangeBtn, true);
            document.querySelectorAll('.chart-container').forEach(container => {
                setChartLoading(container, true);
            });

            // Fetch the custom date range data from the server
            fetch(`/analysis/data/custom?start=${startDate}&end=${endDate}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    // Update spending trend chart with the new data
                    if (data.trendDates && data.trendAmounts) {
                        spendingTrendChart.data.labels = data.trendDates;
                        spendingTrendChart.data.datasets[0].data = data.trendAmounts;
                        spendingTrendChart.update();
                    }

                    // Update category breakdown chart
                    if (data.categoryNames && data.categoryAmounts) {
                        categoryBreakdownChart.data.labels = data.categoryNames;
                        categoryBreakdownChart.data.datasets[0].data = data.categoryAmounts;
                        categoryBreakdownChart.update();

                        // Update the category breakdown table
                        const tableBody = document.getElementById('categoryBreakdownTable');
                        tableBody.innerHTML = '';

                        if (data.categoryNames.length) {
                            const total = data.categoryAmounts.reduce((sum, amount) => sum + amount, 0);
                            data.categoryNames.forEach((name, index) => {
                                const amount = data.categoryAmounts[index];
                                const percentage = total > 0 ? (amount / total * 100).toFixed(1) : 0;

                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${name}</td>
                                    <td>₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                    <td>${percentage}%</td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            row.innerHTML = '<td colspan="3">No data available</td>';
                            tableBody.appendChild(row);
                        }
                    }

                    // Update the summary metrics (total spending, daily average)
                    if (data.totalSpending !== undefined) {
                        document.getElementById('totalSpending').textContent =
                            data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        document.getElementById('dailyAverage').textContent =
                            data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        document.getElementById('totalSpendingCard').textContent =
                            data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        document.getElementById('dailyAverageCard').textContent =
                            data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    }

                    // Update the title with the custom date range
                    document.getElementById('spendingTitle').innerText =
                        `Spending from ${new Date(startDate).toLocaleDateString()} to ${new Date(endDate).toLocaleDateString()}`;
                })
                .catch(error => {
                    console.error('Error loading custom date range data:', error);
                    alert('Failed to load data for the selected date range. Please try again.');
                })
                .finally(() => {
                    // Reset loading states
                    setLoading(applyDateRangeBtn, false);
                    document.querySelectorAll('.chart-container').forEach(container => {
                        setChartLoading(container, false);
                    });
                });
        });

        });

</script>
@endsection