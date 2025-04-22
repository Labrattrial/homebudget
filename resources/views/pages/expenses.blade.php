@extends('layouts.main')

@section('content')
@vite(['resources/css/expenses.css'])

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
          <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="{{ $expense->id }}"></i>
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
</div>

<script>
  function editCard(button) {
  if (isLoading) return;

  const id = button.getAttribute('data-id');
  const card = document.querySelector(`.expense-card[data-id="${id}"]`);
  if (!card) return;

  // Pre-fill form with current values
  const category = card.querySelector('.category').innerText;
  const amount = card.querySelector('.amount').innerText.replace(/[₱,]/g, '');
  const date = card.querySelector('.date').innerText;

  // Set form values
  document.getElementById("modalTitle").innerText = "Edit Expense";
  document.getElementById("modalCategory").value = Array.from(document.getElementById("modalCategory").options)
    .find(option => option.text === category)?.value || '';
  document.getElementById("modalAmount").value = amount;
  document.getElementById("modalDate").value = date;

  editingId = id;
  document.getElementById("expenseModal").style.display = "flex";
}

</script>

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


<script>
  let isLoading = false; // Prevent spamming
  let editingId = null;

  // Toggles loading animation and disables buttons during processing
  function toggleLoading(state, button = null) {
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    isLoading = state;

    if (state) {
      if (button) button.classList.add("disabled-button"); // Disable the button
      if (buttonText) buttonText.style.display = 'none';
      if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
    } else {
      if (button) button.classList.remove("disabled-button"); // Enable the button
      if (buttonText) buttonText.style.display = 'inline-block';
      if (loadingSpinner) loadingSpinner.style.display = 'none';
    }
  }

  // Show confirmation message
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

    // Remove the confirmation message after 3 seconds
    setTimeout(() => confirmation.remove(), 3000);
  }

  // Open the modal for adding a new expense
  function openModal() {
    if (isLoading) return;
    editingId = null;
    document.getElementById("modalTitle").innerText = "Add New Expense";
    document.getElementById("modalCategory").value = '';
    document.getElementById("modalAmount").value = '';
    document.getElementById("modalDate").value = '';
    document.getElementById("expenseModal").style.display = "flex";
  }

  // Close the modal
  function closeModal() {
    if (isLoading) return;
    document.getElementById("expenseModal").style.display = "none";
  }

  // Handle Add or Update Expense Form Submission
  // Handle Add or Update Expense Form Submission
document.getElementById("expenseForm").onsubmit = function (event) {
  event.preventDefault();
  if (isLoading) return;

  const confirmAction = confirm(editingId ? "Are you sure you want to update this expense?" : "Are you sure you want to add this expense?");
  if (!confirmAction) return;

  const submitButton = document.getElementById("submitButton");
  toggleLoading(true, submitButton);

  const form = new FormData(document.getElementById("expenseForm"));
  
  // For updates, we need to add the _method field for Laravel to recognize it as PUT
  if (editingId) {
    form.append('_method', 'PUT');
  }

  const url = editingId ? `/expenses/${editingId}` : "{{ route('user.expenses.store') }}";

  fetch(url, {
    method: 'POST', // Always POST, but with _method for updates
    body: form,
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
        // Update the existing card in the UI
        const card = document.querySelector(`.expense-card[data-id="${editingId}"]`);
        if (card) {
          card.querySelector('.category').innerText = document.querySelector('#modalCategory option:checked').innerText;
          card.querySelector('.amount').innerText = `₱${Number(data.data.amount).toFixed(2)}`;
          card.querySelector('.date').innerText = data.data.date;
        }
      } else {
        // Add new card to the UI
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
      showConfirmationMessage(editingId ? 'Expense updated successfully!' : 'Expense saved successfully!', true);
      closeModal();
    } else {
      showConfirmationMessage(data.message || 'Failed to save expense.', false);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    const errorMessage = error.message || 'An error occurred while saving the expense.';
    showConfirmationMessage(errorMessage, false);
  })
  .finally(() => {
    toggleLoading(false, submitButton);
    editingId = null; // Reset editing ID
  });
};
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // This function will only run after the DOM is fully loaded.
  document.addEventListener('DOMContentLoaded', function () {
    // Ensure that the canvas element is visible
    const categoryBreakdownSection = document.getElementById('categoryBreakdownSection');
    categoryBreakdownSection.style.display = 'block'; // Ensure it's visible when initializing the chart

    // Prepare the data
    const categorySummary = JSON.parse('{!! json_encode($categorySummary) !!}');
    const categories = Object.keys(categorySummary);
    const expenses = Object.values(categorySummary);
    const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

    // Insert breakdown list items into the list
    const breakdownList = document.getElementById('breakdownList');
    categories.forEach((cat, index) => {
      const item = document.createElement('span');
      item.innerHTML = `
        <span>${cat}</span>
        <span style="color: ${colors[index % colors.length]}">₱${parseFloat(categorySummary[cat]).toLocaleString()}</span>
      `;
      breakdownList.appendChild(item);
    });

    // Initialize the Chart.js pie chart
    const ctx = document.getElementById('categoryBreakdownChart').getContext('2d');
    const categoryBreakdownChart = new Chart(ctx, {
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
  });
</script>

<style>
  /* Hide category breakdown section initially */
  #categoryBreakdownSection {
    display: none;
  }
</style>

<script>
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

  // Ensure category section is hidden by default
  document.addEventListener("DOMContentLoaded", function() {
    toggleTab('recent'); // Show the recent expenses tab and hide the category breakdown by default
  });
</script>

@endsection