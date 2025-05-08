@extends('layouts.main')

@section('content')
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/analysis.css', 'resources/js/chart.js', 'resources/js/fontawesome.js'])

<div class="dashboard-container">
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
            <p>Total Spending: <span class="currency">{{ Auth::user()->currency_symbol }}</span><span id="totalSpending">{{ number_format($totalSpending ?? 0, 2) }}</span></p>
            <p>Daily Average: <span class="currency">{{ Auth::user()->currency_symbol }}</span><span id="dailyAverage">{{ number_format($dailyAverage ?? 0, 2) }}</span></p>
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
                    <p class="amount">₱<span id="potentialSavings">0.00</span></p>
                    <p class="change">Based on 10% reduction</p>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="card-content">
                    <h3>Daily Average</h3>
                    <p class="amount">₱<span id="dailyAverageCard">{{ number_format($dailyAverage ?? 0, 2) }}</span></p>
                    <p class="change">Current period</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="chart-row">
            <!-- Spending Trend Chart -->
            <div class="chart-container">
                <div class="chart-header">
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
                </div>
                <div class="chart-content">
                    <div class="chart-loading-overlay">
                        <div class="chart-spinner"></div>
                    </div>
                    <div class="no-data-message" style="display: none;">
                        <i class="fas fa-chart-line"></i>
                        <p>No spending data available for the selected period</p>
                    </div>
                    <canvas id="spendingTrendChart"></canvas>
                </div>
            </div>

            <!-- Category Breakdown Chart -->
            <div class="chart-container">
                <div class="chart-header">
                <h2>Category Breakdown</h2>
                </div>
                <div class="chart-content">
                    <div class="chart-loading-overlay">
                        <div class="chart-spinner"></div>
                    </div>
                    <div class="no-data-message" style="display: none;">
                        <i class="fas fa-pie-chart"></i>
                        <p>No category data available for the selected period</p>
                    </div>
                    <canvas id="categoryBreakdownChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown Table -->
        <div class="detailed-breakdown">
            <div class="table-header">
                <div class="table-header-left">
            <h2>Detailed Category Breakdown</h2>
                </div>
                <div class="table-header-right">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" placeholder="Search categories...">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <div class="no-data-message" style="display: none;">
                    <i class="fas fa-table"></i>
                    <p>No category breakdown data available for the selected period</p>
                </div>
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
</div>

<style>
/* Chart Container Styles */
.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.chart-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    height: 500px;
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Prevent overflow */
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
    flex-shrink: 0; /* Prevent header from shrinking */
}

.chart-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

.chart-tabs {
    display: flex;
    gap: 0.5rem;
}

.chart-filter-btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.chart-filter-btn.active {
    background-color: #4a5568;
    color: white;
}

.chart-content {
    flex: 1;
    position: relative;
    min-height: 0; /* Allow content to shrink */
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-content canvas {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Table Styles */
.detailed-breakdown {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    margin-top: 2rem;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.table-header-left {
    display: flex;
    align-items: center;
}

.table-header-right {
    display: flex;
    align-items: center;
}

.table-search {
    position: relative;
    width: 300px;
    display: flex;
    align-items: center;
}

.table-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 0.875rem;
}

.table-search input {
    padding-left: 2.5rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    height: 38px;
    width: 100%;
}

.table-wrapper {
    position: relative;
    overflow-x: auto;
}

#categoryBreakdownTable {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

#categoryBreakdownTable thead th {
    background-color: #f7fafc;
    padding: 1rem;
    font-weight: 600;
    color: #4a5568;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
}

#categoryBreakdownTable tbody td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #4a5568;
}

#categoryBreakdownTable tbody tr:hover {
    background-color: #f7fafc;
}

/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    display: none; /* Hide duplicate search and length controls */
}

.dataTables_wrapper .dataTables_info {
    color: #4a5568;
    padding: 1rem 0;
}

.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    display: none;
}

/* Loading States */
.chart-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.chart-loading-overlay.active {
    opacity: 1;
    visibility: visible;
}

.chart-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #4a5568;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* No Data Message */
.no-data-message {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #a0aec0;
}

.no-data-message i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.no-data-message p {
    margin: 0;
    font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 400px;
    }
}

@media (max-width: 768px) {
    .chart-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .chart-tabs {
        width: 100%;
        justify-content: center;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-search {
        width: 100%;
    }
}
</style>

<script>
let spendingTrendChart = null;
let categoryBreakdownChart = null;
let breakdownTable = null;

function initializeCharts() {
    breakdownTable = $('#categoryBreakdownTable').DataTable({
        responsive: true,
        dom: '<"top"B>rt',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fas fa-download"></i> Export',
                className: 'btn btn-outline-secondary',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i> Copy',
                        className: 'btn btn-outline-secondary'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-outline-secondary'
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        className: 'btn btn-outline-secondary'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-outline-secondary'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn btn-outline-secondary'
                    }
                ]
            }
        ],
        order: [[1, 'desc']],
        language: {
            emptyTable: "No data available in table",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        columnDefs: [
            { className: "text-left", targets: [0] },
            { className: "text-right", targets: [1, 2, 3, 4] }
        ]
    });

    // Connect the custom search input to DataTable
    $('.table-search input').on('keyup', function() {
        breakdownTable.search(this.value).draw();
    });

    // Initialize charts with empty data
    const trendCtx = document.getElementById('spendingTrendChart').getContext('2d');
    const categoryCtx = document.getElementById('categoryBreakdownChart').getContext('2d');

    spendingTrendChart = new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Spending Trend',
                data: [],
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
                            return '{{ Auth::user()->currency_symbol }}' + value.toLocaleString('en-PH', { minimumFractionDigits: 2 });
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
                    title: { display: true, text: 'Amount ({{ Auth::user()->currency_symbol }})' }, 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '{{ Auth::user()->currency_symbol }}' + value.toLocaleString('en-PH');
                        }
                    }
                }
            }
        }
    });

    categoryBreakdownChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                    'rgba(255, 159, 64, 0.5)',
                    'rgba(199, 199, 199, 0.5)',
                    'rgba(83, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${context.label}: {{ Auth::user()->currency_symbol }}${value.toLocaleString('en-PH', { minimumFractionDigits: 2 })} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function updateCharts(data) {
    // Update spending trend chart
    if (data.trendDates && data.trendDates.length > 0) {
        spendingTrendChart.data.labels = data.trendDates;
        spendingTrendChart.data.datasets[0].data = data.trendAmounts;
        spendingTrendChart.update();
        $('#spendingTrendChart').parent().find('.no-data-message').hide();
    } else {
        $('#spendingTrendChart').parent().find('.no-data-message').show();
    }

    // Update category breakdown chart
    if (data.categoryNames && data.categoryNames.length > 0) {
        categoryBreakdownChart.data.labels = data.categoryNames;
        categoryBreakdownChart.data.datasets[0].data = data.categoryAmounts;
        categoryBreakdownChart.update();
        $('#categoryBreakdownChart').parent().find('.no-data-message').hide();
    } else {
        $('#categoryBreakdownChart').parent().find('.no-data-message').show();
    }

    // Update summary metrics
    $('#totalSpending').text(data.totalSpending.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#dailyAverage').text(data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#dailyAverageCard').text(data.dailyAverage.toLocaleString('en-PH', { minimumFractionDigits: 2 }));

    // Update potential savings
    const potentialSavings = data.totalSpending * 0.1;
    $('#potentialSavings').text(potentialSavings.toLocaleString('en-PH', { minimumFractionDigits: 2 }));

    // Update budget progress
    if (data.budgetAmount > 0) {
        const progress = (data.totalSpending / data.budgetAmount) * 100;
        $('#budgetProgressFill').css('width', `${Math.min(progress, 100)}%`);
        $('#budgetProgressText').text(`${progress.toFixed(1)}% of Budget`);
        
        // Update progress bar color based on utilization
        const progressFill = $('#budgetProgressFill');
        if (progress > 90) {
            progressFill.css('background-color', '#dc3545');
        } else if (progress > 70) {
            progressFill.css('background-color', '#ffc107');
        } else {
            progressFill.css('background-color', '#28a745');
        }
    }

    // Update table data
    breakdownTable.clear();
    if (data.categoryNames && data.categoryNames.length > 0) {
        data.categoryNames.forEach((name, index) => {
            const amount = data.categoryAmounts[index];
            const percentage = data.totalSpending > 0 ? ((amount / data.totalSpending) * 100).toFixed(1) : 0;
            
            breakdownTable.row.add([
                name,
                '{{ Auth::user()->currency_symbol }}' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2 }),
                '{{ Auth::user()->currency_symbol }}' + data.budgetAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 }),
                data.budgetAmount > 0 ? `${((amount / data.budgetAmount) * 100).toFixed(1)}%` : 'N/A',
                '{{ Auth::user()->currency_symbol }}' + percentage + '%'
            ]);
        });
    }
    breakdownTable.draw();

    // Show/hide no data message for table
    if (data.categoryNames && data.categoryNames.length > 0) {
        $('.table-wrapper .no-data-message').hide();
    } else {
        $('.table-wrapper .no-data-message').show();
    }
}

function loadData(startDate, endDate, viewType = 'monthly') {
    showLoading();
    
    // Update the date inputs to match the selected range
    $('#startDate').val(startDate);
    $('#endDate').val(endDate);
    
    fetch(`/analysis/data?start=${startDate}&end=${endDate}&view=${viewType}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Failed to load data');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.message || data.error);
            }
            updateCharts(data);
            hideLoading();
        })
        .catch(error => {
            console.error('Error loading data:', error);
            hideLoading();
            showError(error.message || 'Failed to load analysis data. Please try again.');
        });
}

function showLoading() {
    $('.chart-loading-overlay').addClass('active');
    $('#applyDateRange').addClass('button-loading');
    $('.chart-filter-btn').prop('disabled', true);
}

function hideLoading() {
    $('.chart-loading-overlay').removeClass('active');
    $('#applyDateRange').removeClass('button-loading');
    $('.chart-filter-btn').prop('disabled', false);
}

function showError(message) {
    const errorHtml = `
        <div class="error-message">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Error</h4>
                    <p>${message}</p>
                    <div class="error-actions">
                        <button class="btn btn-primary retry-btn">
                            <i class="fas fa-sync-alt"></i> Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove any existing error messages
    $('.error-message').remove();
    
    // Add new error message
    $('.analysis-wrapper').prepend(errorHtml);
    
    // Add retry functionality
    $('.retry-btn').click(function() {
        $('.error-message').remove();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const viewType = $('.chart-filter-btn.active').data('type');
        loadData(startDate, endDate, viewType);
    });
}

// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Load initial data
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    loadData(startDate, endDate);

    // Event listeners
    $('#applyDateRange').click(function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate || !endDate) {
            showError('Please select both start and end dates');
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (start > end) {
            showError('Start date cannot be after end date');
            return;
        }

        const viewType = $('.chart-filter-btn.active').data('type');
        loadData(startDate, endDate, viewType);
    });

    $('.range-preset').click(function() {
        const days = $(this).data('days');
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);
        
        const formattedStartDate = startDate.toISOString().split('T')[0];
        const formattedEndDate = endDate.toISOString().split('T')[0];
        
        const viewType = $('.chart-filter-btn.active').data('type');
        loadData(formattedStartDate, formattedEndDate, viewType);
    });

    $('.chart-filter-btn').click(function() {
        $('.chart-filter-btn').removeClass('active');
        $(this).addClass('active');
        const viewType = $(this).data('type');
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        loadData(startDate, endDate, viewType);
    });

    // Spending visibility toggle
    $('#spendingToggle').change(function() {
        document.querySelector('.analysis-wrapper').classList.toggle('hide-spending', !this.checked);
        localStorage.setItem('spendingVisibility', this.checked);
    });

    // Load saved preference on page load
    const spendingToggle = $('#spendingToggle');
    const savedVisibility = localStorage.getItem('spendingVisibility');
    if (savedVisibility !== null) {
        spendingToggle.prop('checked', savedVisibility === 'true');
        document.querySelector('.analysis-wrapper').classList.toggle('hide-spending', !spendingToggle.prop('checked'));
    }
});
</script>
@endsection

