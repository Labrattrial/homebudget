@extends('layouts.main')

@section('content')
<link href="{{ asset('resources/css/dashboard.css') }}" rel="stylesheet">
<script src="{{ asset('resources/js/chart.js') }}"></script>

<div class="welcome-card">
  <div>
    <h1>Welcome, {{ isset($user->name) ? $user->name : 'User' }}!</h1>
    <p>Here's a quick overview of your finances</p>
  </div>
  <i class="fas fa-coins fa-3x"></i>
</div>

<div class="stats">
  <!-- MONTHLY BUDGET PLANNER -->
  <div class="card">
    <h3>MONTHLY BUDGET PLANNER</h3>
    <table class="budget-table">
      <thead>
        <tr>
          <th>Category</th>
          <th>Spent This Month</th>
          <th>Budget Limit</th>
          <th>Remaining</th>
        </tr>
      </thead>
      <tbody>
        @foreach($categoryAnalysis as $category)
        <tr>
          <td>{{ $category['name'] }}</td>
          <td>₱{{ number_format($category['spent'], 2) }}</td>
          <td>₱{{ isset($category['budget']) ? number_format($category['budget'], 2) : number_format(0, 2) }}</td>
          <td class="{{ (isset($category['remaining']) && $category['remaining'] < 0) ? 'text-danger' : '' }}">
            {{ isset($category['remaining']) ? '₱'.number_format($category['remaining'], 2) : 'N/A' }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- CATEGORY BREAKDOWN -->
  <div class="card">
    <i class="fas fa-chart-pie fa-2x" style="color:#4b8dbf;"></i>
    <h3>CATEGORY BREAKDOWN</h3>
    <div style="height: 300px;">
      <canvas id="pieChart"></canvas>
      <div class="chart-loading" style="display: none;">Loading data...</div>
      <div class="chart-error" style="display: none;">Error loading chart data</div>
      <div class="no-data-message" style="display: none;">No expenses recorded yet</div>
    </div>
  </div>

  <!-- MONTHLY EXPENSES -->
  <div class="card">
    <i class="fas fa-chart-bar fa-2x" style="color:#4b8dbf;"></i>
    <h3>MONTHLY EXPENSES</h3>
    <div style="height: 300px;">
      <canvas id="barChart"></canvas>
      <div class="chart-loading" style="display: none;">Loading data...</div>
      <div class="chart-error" style="display: none;">Error loading chart data</div>
      <div class="no-data-message" style="display: none;">No expenses recorded yet</div>
    </div>
  </div>
</div>

<script>
  // Chart configurations
  var pieChartConfig = {
    type: 'pie',
    data: {
      labels: JSON.parse(`{!! json_encode(array_column($categoryData, 'name')) !!}`),
      datasets: [{
        data: JSON.parse(`{!! json_encode(array_column($categoryData, 'total')) !!}`),
        backgroundColor: [
          '#4b8dbf', '#3a6f96', '#2a516d', '#193244', '#08141b',
          '#5da5d8', '#4c94c7', '#3b83b6', '#2a72a5', '#196194'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      tooltips: {
        callbacks: {
          label: function(tooltipItem, data) {
            var label = data.labels[tooltipItem.index] || '';
            var value = data.datasets[0].data[tooltipItem.index];
            return label + ': ₱' + value.toFixed(2);
          }
        }
      }
    }
  };

  var barChartConfig = {
    type: 'bar',
    data: {
      data: JSON.parse(`{!! json_encode(array_column($categoryData, 'name')) !!}`),
      datasets: [{
        label: 'Amount Spent (₱)',
        data: JSON.parse(`{!! json_encode(array_column($categoryData, 'total')) !!}`),
        backgroundColor: '#4b8dbf',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true
          }
        }]
      }
    }
  };

  // Initialize charts when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    try {
      // Pie Chart
      var pieCtx = document.getElementById('pieChart').getContext('2d');
      new Chart(pieCtx, pieChartConfig);
      
      // Bar Chart
      var barCtx = document.getElementById('barChart').getContext('2d');
      new Chart(barCtx, barChartConfig);
      
    } catch (error) {
      console.error('Error initializing charts:', error);
      var errorElements = document.querySelectorAll('.chart-error');
      for (var i = 0; i < errorElements.length; i++) {
        errorElements[i].style.display = 'block';
      }
    }
  });
</script>

<style>
  /* Chart status messages */
  .chart-loading,
  .chart-error,
  .no-data-message {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #666;
  }
  
  .text-danger {
    color: #e74c3c;
  }
  
  /* Budget table styles */
  .budget-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .budget-table th, .budget-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
  }
  
  .budget-table th {
    font-weight: 600;
    color: #2a516d;
  }
</style>
@endsection