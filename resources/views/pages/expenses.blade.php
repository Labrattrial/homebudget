@extends('layouts.main')

@section('content')
@vite(['resources/css/expenses.css', 'resources/js/chart.js', 'resources/js/fontawesome.js'])

<div class="expenses-wrapper">
  <!-- Header Tabs -->
  <div class="expenses-header">
    <button class="tab active" id="recentExpensesTab" onclick="toggleTab('recent')">Recent Expenses</button>
    <button class="tab" id="categoryBreakdownTab" onclick="toggleTab('category')">Category Breakdown</button>
    <div class="add-btn" onclick="openModal()">+</div>
  </div>

  <!-- Expense Cards (Recent Expenses) -->
  <div class="expenses-grid" id="recentExpensesGrid">
    @foreach ($expenses as $expense)
      <div class="expense-card" data-id="{{ $expense->id }}">
        <div class="expense-icons">
          <i class="fas fa-edit edit-icon" onclick="editCard(this)" title="Edit" data-id="{{ $expense->id }}"></i>
          <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="{{ $expense->id }}">
          <span class="delete-spinner spinner" style="display: none;"></span>
          </i>
        </div>
        <p><strong>Category:</strong> <span class="category">{{ $expense->category->name }}</span></p>
        <p><strong>Total Expense:</strong> <span class="amount">₱{{ number_format($expense->amount, 2) }}</span></p>
        <p><strong>Date:</strong> <span class="date">{{ $expense->date }}</span></p>
      </div>
    @endforeach
  </div>

  <!-- Modal -->
  <div id="expenseModal" class="modal" role="dialog" aria-hidden="true">
    <div class="modal-content">
      <button class="close" onclick="closeModal()" aria-label="Close">&times;</button>
      <h2 id="modalTitle">Add New Expense</h2>
      <form id="expenseForm">
        @csrf
        <label for="modalCategory">Category:</label>
        <select id="modalCategory" name="category_id" required>
          <option value="" disabled selected>Select a category</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>

        <label for="modalAmount">Total Expense:</label>
        <input type="number" name="amount" id="modalAmount" required placeholder="Enter amount" />

        <label for="modalDate">Date:</label>
        <input type="date" name="date" id="modalDate" required />

        <button type="submit" class="save-btn" id="submitButton">
          <span id="buttonText">Save</span>
          <span id="loadingSpinner" class="spinner" style="display: none;"></span>
        </button>
      </form>
    </div>
  </div>

  <!-- Category Breakdown Card -->
  <div class="category-card" id="categoryBreakdownSection" style="display: none;">
    <h2>Category Breakdown</h2>
    <div class="chart-container">
      <canvas id="categoryBreakdownChart"></canvas>
    </div>
    <div class="breakdown-list" id="breakdownList">
      <!-- Dynamic items inserted via JS -->
    </div>
  </div>
</div>

<script>
  let isLoading = false;
  let editingId = null;
  let categoryBreakdownChart = null;

  // Initialize the application when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    toggleTab('recent');
    initializeChart();
  });

  // Initialize or update the chart
  function initializeChart() {
  const chartContainer = document.querySelector('.chart-container');
  const loadingElem = document.createElement('div');
  loadingElem.className = 'chart-loading';
  loadingElem.textContent = 'Loading data...';
  chartContainer.appendChild(loadingElem);
  
  try {
    const categorySummary = JSON.parse('{!! json_encode($categorySummary) !!}');
    updateChart(categorySummary);
  } catch (e) {
    console.error('Error loading chart data:', e);
    chartContainer.innerHTML = '<div class="no-data-message">Error loading chart data</div>';
    document.getElementById('breakdownList').innerHTML = '<span class="no-data">Error loading data</span>';
  } finally {
    loadingElem.remove();
  }
}

  // Update the chart with new data
  function updateChart(categorySummary) {
  const categories = Object.keys(categorySummary);
  const expenses = Object.values(categorySummary);
  const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
  
  // Update breakdown list
  const breakdownList = document.getElementById('breakdownList');
  breakdownList.innerHTML = '';

  // Get or create chart
  const ctx = document.getElementById('categoryBreakdownChart').getContext('2d');
  
  // Handle empty state
  if (categories.length === 0 || expenses.every(e => e === 0)) {
    // Create empty chart data
    const emptyData = {
      labels: ['No Expenses'],
      datasets: [{
        data: [1],
        backgroundColor: ['#f5f5f5'],
        borderWidth: 1
      }]
    };

    if (categoryBreakdownChart) {
      // Update existing chart with empty state
      categoryBreakdownChart.data = emptyData;
      categoryBreakdownChart.update();
    } else {
      // Create new chart with empty state
      categoryBreakdownChart = new Chart(ctx, {
        type: 'pie',
        data: emptyData,
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'bottom',
            },
            tooltip: {
              enabled: false
            }
          }
        }
      });
    }

    breakdownList.innerHTML = '<span class="no-data">No expenses recorded yet</span>';
    return;
  }

  // Create list items for each category
  categories.forEach((cat, index) => {
    const item = document.createElement('span');
    item.innerHTML = `
      <span>${cat}</span>
      <span style="color: ${colors[index % colors.length]}">₱${parseFloat(categorySummary[cat]).toLocaleString()}</span>
    `;
    breakdownList.appendChild(item);
  });

  if (categoryBreakdownChart) {
    // Update existing chart
    categoryBreakdownChart.data.labels = categories;
    categoryBreakdownChart.data.datasets[0].data = expenses;
    categoryBreakdownChart.data.datasets[0].backgroundColor = colors;
    categoryBreakdownChart.options.plugins.tooltip.enabled = true;
    categoryBreakdownChart.update();
  } else {
    // Create new chart
    categoryBreakdownChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: categories,
        datasets: [{
          label: 'Category Breakdown',
          data: expenses,
          backgroundColor: colors,
          borderWidth: 1,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            callbacks: {
              label: function(tooltipItem) {
                const value = tooltipItem.raw;
                return `${tooltipItem.label}: ₱${value.toLocaleString()}`;
              }
            }
          },
        },
      },
    });
  }
}

  function toggleLoading(state, button = null) {
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    isLoading = state;

    if (state) {
      if (button) button.classList.add("disabled-button");
      if (buttonText) buttonText.style.display = 'none';
      if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
    } else {
      if (button) button.classList.remove("disabled-button");
      if (buttonText) buttonText.style.display = 'inline-block';
      if (loadingSpinner) loadingSpinner.style.display = 'none';
    }
  }

  function showConfirmationMessage(message, success = true) {
    const confirmation = document.createElement('div');
    confirmation.classList.add('confirmation-message');
    confirmation.textContent = message;

    if (success) {
      confirmation.classList.add('success');
    } else {
      confirmation.classList.add('error');
    }

    document.body.appendChild(confirmation);
    setTimeout(() => confirmation.remove(), 3000);
  }

  function openModal() {
    if (isLoading) return;
    editingId = null;
    document.getElementById("modalTitle").innerText = "Add New Expense";
    document.getElementById("modalCategory").value = '';
    document.getElementById("modalAmount").value = '';
    document.getElementById("modalDate").value = '';
    document.getElementById("expenseModal").style.display = "flex";
  }

  function closeModal() {
    if (isLoading) return;
    document.getElementById("expenseModal").style.display = "none";
  }

  function editCard(button) {
    if (isLoading) return;

    const id = button.getAttribute('data-id');
    const card = document.querySelector(`.expense-card[data-id="${id}"]`);
    if (!card) return;

    const category = card.querySelector('.category').innerText;
    const amount = card.querySelector('.amount').innerText.replace(/[₱,]/g, '');
    const date = card.querySelector('.date').innerText;

    document.getElementById("modalTitle").innerText = "Edit Expense";
    document.getElementById("modalCategory").value = Array.from(document.getElementById("modalCategory").options)
      .find(option => option.text === category)?.value || '';
    document.getElementById("modalAmount").value = amount;
    document.getElementById("modalDate").value = date;

    editingId = id;
    document.getElementById("expenseModal").style.display = "flex";
  }

  function deleteCard(button) {
  if (isLoading) return;
  
  const id = button.getAttribute('data-id');
  const confirmAction = confirm("Are you sure you want to delete this expense?");
  if (!confirmAction) return;

  // Show loading on the delete button
  const deleteIcon = button.querySelector('.fa-trash');
  const deleteSpinner = button.querySelector('.delete-spinner');
  
  // Hide the trash icon and show spinner
  if (deleteIcon) deleteIcon.style.display = 'none';
  if (deleteSpinner) deleteSpinner.style.display = 'inline-block';
  button.classList.add('loading');

  fetch(`/expenses/${id}`, {
      method: 'DELETE',
      headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
      },
  })
  .then(response => {
      if (!response.ok) {
          return response.json().then(err => { throw err; });
      }
      return response.json();
  })
  .then(data => {
      if (data.success) {
          document.querySelector(`.expense-card[data-id="${id}"]`)?.remove();
          updateChart(data.categorySummary);
          showConfirmationMessage('Expense deleted successfully!', true);
      } else {
          showConfirmationMessage(data.message || 'Failed to delete expense.', false);
      }
  })
  .catch(error => {
      console.error('Error:', error);
      showConfirmationMessage(error.message || 'An error occurred while deleting the expense.', false);
  })
  .finally(() => {
      // Hide loading spinner and show icon again
      if (deleteIcon) deleteIcon.style.display = 'inline-block';
      if (deleteSpinner) deleteSpinner.style.display = 'none';
      button.classList.remove('loading');
  });
}

  // Handle form submission
  document.getElementById("expenseForm").onsubmit = function(event) {
  event.preventDefault();
  if (isLoading) return;

  // Clear previous invalid states
  document.querySelectorAll('#expenseForm input, #expenseForm select').forEach(el => {
    el.classList.remove('invalid');
  });

  // Validate form
  let isValid = true;
  const form = document.getElementById("expenseForm");
  const requiredFields = form.querySelectorAll('[required]');
  
  requiredFields.forEach(field => {
    if (!field.value) {
      field.classList.add('invalid');
      isValid = false;
    }
  });

  if (!isValid) {
    showConfirmationMessage('Please fill in all required fields', false);
    return;
  }

  const confirmAction = confirm(editingId ? "Are you sure you want to update this expense?" : "Are you sure you want to add this expense?");
  if (!confirmAction) return;

  const submitButton = document.getElementById("submitButton");
  toggleLoading(true, submitButton);

  const formData = new FormData(document.getElementById("expenseForm"));
  
  if (editingId) {
    formData.append('_method', 'PUT');
  }

  const url = editingId ? `/expenses/${editingId}` : "{{ route('user.expenses.store') }}";

  fetch(url, {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
  })
  .then(response => {
    if (!response.ok) {
      return response.json().then(err => { throw err; });
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      if (editingId) {
        // Update existing card
        const card = document.querySelector(`.expense-card[data-id="${editingId}"]`);
        if (card) {
          card.querySelector('.category').innerText = document.querySelector('#modalCategory option:checked').innerText;
          card.querySelector('.amount').innerText = `₱${Number(data.data.amount).toFixed(2)}`;
          card.querySelector('.date').innerText = data.data.date;
        }
      } else {
        // Add new card
        const recentExpensesGrid = document.getElementById("recentExpensesGrid");
        const newCard = document.createElement("div");
        newCard.classList.add("expense-card");
        newCard.setAttribute("data-id", data.data.id);
        newCard.innerHTML = `
          <div class="expense-icons">
            <i class="fas fa-edit edit-icon" onclick="editCard(this)" title="Edit" data-id="${data.data.id}"></i>
            <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="${data.data.id}"></i>
          </div>
          <p><strong>Category:</strong> <span class="category">${document.querySelector('#modalCategory option:checked').innerText}</span></p>
          <p><strong>Total Expense:</strong> <span class="amount">₱${Number(data.data.amount).toFixed(2)}</span></p>
          <p><strong>Date:</strong> <span class="date">${data.data.date}</span></p>
        `;
        recentExpensesGrid.insertBefore(newCard, recentExpensesGrid.firstChild);
      }
      
      // Update chart with new data
      updateChart(data.categorySummary);
      showConfirmationMessage(editingId ? 'Expense updated successfully!' : 'Expense saved successfully!', true);
      closeModal();
    } else {
      showConfirmationMessage(data.message || 'Failed to save expense.', false);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    
    // Handle validation errors from server
    if (error.errors) {
      Object.entries(error.errors).forEach(([field, messages]) => {
        const input = document.querySelector(`#expenseForm [name="${field}"]`);
        if (input) {
          input.classList.add('invalid');
          const errorMsg = document.createElement('div');
          errorMsg.className = 'invalid-field';
          errorMsg.textContent = messages.join(', ');
          input.parentNode.insertBefore(errorMsg, input.nextSibling);
        }
      });
      showConfirmationMessage('Please correct the errors in the form', false);
    } else {
      showConfirmationMessage(error.message || 'An error occurred while saving the expense.', false);
    }
  })
  .finally(() => {
    toggleLoading(false, submitButton);
  });
};

  function toggleTab(tabName) {
    const recentTab = document.getElementById('recentExpensesTab');
    const categoryTab = document.getElementById('categoryBreakdownTab');
    const recentGrid = document.getElementById('recentExpensesGrid');
    const categorySection = document.getElementById('categoryBreakdownSection');
    const addBtn = document.querySelector('.add-btn');

    if (tabName === 'recent') {
      recentTab.classList.add('active');
      categoryTab.classList.remove('active');
      recentGrid.style.display = 'grid';
      categorySection.style.display = 'none';
      addBtn.style.display = 'block'; 
    } else if (tabName === 'category') {
      categoryTab.classList.add('active');
      recentTab.classList.remove('active');
      recentGrid.style.display = 'none';
      categorySection.style.display = 'block';
      addBtn.style.display = 'none';
    }
  }
</script>

<style>
  /* Confirmation message styles */
  .confirmation-message {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
  }
  
  .confirmation-message.success {
    background-color: #4CAF50;
  }
  
  .confirmation-message.error {
    background-color: #F44336;
  }
</style>
@endsection