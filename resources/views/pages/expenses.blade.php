@extends('layouts.main')

@section('content')
@vite(['resources/css/expenses.css', 'resources/js/fontawesome.js'])

<div class="expenses-wrapper">
  <!-- Header with title, filter, search bar, and add button -->
  <div class="expenses-header">
    <h2>Recent Expenses</h2>
    <div class="header-actions">
      <input type="text" id="searchInput" placeholder="Search by description or category" oninput="searchExpenses()" />
      <select id="categoryFilter" onchange="filterExpenses(this.value)">
        <option value="">All Categories</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
      </select>
      <div class="add-btn" onclick="openModal()">+</div>
    </div>
  </div>

  <!-- Expense Cards -->
  <div class="expenses-grid" id="recentExpensesGrid">
    @foreach ($expenses as $expense)
      <div class="expense-card" data-id="{{ $expense->id }}" data-category="{{ $expense->category_id }}">
        <div class="expense-icons">
          <i class="fas fa-edit edit-icon" onclick="editCard(this)" title="Edit" data-id="{{ $expense->id }}"></i>
          <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="{{ $expense->id }}"></i>
        </div>
        <p><strong>Category:</strong> <span class="category">{{ $expense->category->name }}</span></p>
        <p><strong>Specs:</strong> <span class="specs">{{ $expense->description }}</span></p>
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
        <div class="custom-select">
          <select id="modalCategory" name="category_id" required onchange="fetchSpecsByCategory(this.value)">
            <option value="" disabled selected>Select a category</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="error-message" id="error-category_id"></div>

        <label>Specs:</label>
        <div class="specs-options">
          <input type="radio" id="existingSpecs" name="specs_option" value="existing" checked onclick="toggleSpecsInput('existing')" />
          <label for="existingSpecs">Select Existing</label>
          
          <input type="radio" id="newSpecs" name="specs_option" value="new" onclick="toggleSpecsInput('new')" />
          <label for="newSpecs">Add New</label>
        </div>

        <div id="existingSpecsContainer">
          <div class="custom-select">
            <select id="modalSpecs" name="description">
              <option value="" disabled selected>Select Specifics</option>
              @foreach($specs as $spec)
                <option value="{{ $spec }}">{{ $spec }}</option>
              @endforeach
            </select>
          </div>
          <div class="error-message" id="error-description"></div>
        </div>

        <div id="newSpecsContainer" style="display: none;">
          <input type="text" id="newSpecsInput" name="new_specs" placeholder="Enter new specs" />
          <div class="error-message" id="error-new_specs"></div>
        </div>

        <label for="modalAmount">Total Expense:</label>
        <input type="number" name="amount" id="modalAmount" required placeholder="Enter amount" />
        <div class="error-message" id="error-amount"></div>

        <label for="modalDate">Date:</label>
        <input type="date" name="date" id="modalDate" required />
        <div class="error-message" id="error-date"></div>

        <button type="submit" class="save-btn" id="submitButton">
          <span id="buttonText">Save</span>
          <span id="loadingSpinner" class="spinner" style="display: none;"></span>
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  let isLoading = false;
  let editingId = null;

  function showPageLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);
  }

  function hidePageLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) loadingOverlay.remove();
  }

  function fetchSpecsByCategory(categoryId) {
    fetch(`/categories/${categoryId}/descriptions`, {
      headers: { 'Accept': 'application/json' },
    })
    .then(response => response.json())
    .then(data => {
      const specsDropdown = document.getElementById('modalSpecs');
      specsDropdown.innerHTML = '<option value="" disabled selected>Select specs</option>';
      data.specs.forEach(spec => {
        specsDropdown.insertAdjacentHTML('beforeend', `<option value="${spec}">${spec}</option>`);
      });
    })
    .catch(error => console.error('Error fetching specs:', error));
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
    document.getElementById("modalSpecs").value = '';
    document.getElementById("newSpecsInput").value = '';
    document.getElementById("expenseModal").style.display = "flex";
  }

  function closeModal() {
    if (isLoading) return;
    document.getElementById("expenseModal").style.display = "none";
  }

  function toggleSpecsInput(option) {
    const existingContainer = document.getElementById('existingSpecsContainer');
    const newContainer = document.getElementById('newSpecsContainer');

    if (option === 'existing') {
      existingContainer.style.display = 'block';
      newContainer.style.display = 'none';
    } else {
      existingContainer.style.display = 'none';
      newContainer.style.display = 'block';
    }
  }

  document.getElementById("expenseForm").onsubmit = function(event) {
    event.preventDefault();
    if (isLoading) return;

    showPageLoading(); // Show loading overlay

    const formData = new FormData(document.getElementById("expenseForm"));
    if (editingId) {
      formData.append('_method', 'PUT');
    }

    const url = editingId ? `/expenses/${editingId}` : "{{ route('user.expenses.store') }}";
   

    toggleLoading(true);
    fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showConfirmationMessage('Expense saved successfully!', true);
          closeModal();
          const newExpenseCard = createExpenseCard(data.expense);
          document.getElementById('recentExpensesGrid').prepend(newExpenseCard);
        } else {
          showValidationErrors(data.errors);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showConfirmationMessage('An error occurred while saving the expense.', false);
      })
      .finally(() => {
        toggleLoading(false);
        hidePageLoading(); // Hide loading overlay
      });
  };

  function createExpenseCard(expense) {
    const card = document.createElement('div');
    card.className = 'expense-card';
    card.setAttribute('data-id', expense.id);
    card.setAttribute('data-category', expense.category_id);
    card.innerHTML = `
      <div class="expense-icons">
        <i class="fas fa-edit edit-icon" onclick="editCard(this)" title="Edit" data-id="${expense.id}"></i>
        <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="${expense.id}"></i>
      </div>
      <p><strong>Category:</strong> <span class="category">${expense.category.name}</span></p>
      <p><strong>Specs:</strong> <span class="specs">${expense.description}</span></p>
      <p><strong>Total Expense:</strong> <span class="amount">₱${parseFloat(expense.amount).toFixed(2)}</span></p>
      <p><strong>Date:</strong> <span class="date">${expense.date}</span></p>
    `;
    return card;
  }

  function showValidationErrors(errors) {
    Object.keys(errors).forEach(key => {
      const errorElement = document.getElementById(`error-${key}`);
      if (errorElement) {
        errorElement.textContent = errors[key][0]; // Show the first error message
      }
    });
  }

  function filterExpenses(categoryId) {
    const cards = document.querySelectorAll('.expense-card');
    cards.forEach(card => {
      if (categoryId === "" || card.getAttribute('data-category') === categoryId) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function searchExpenses() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.expense-card');
    cards.forEach(card => {
      const description = card.querySelector('.specs').textContent.toLowerCase();
      const category = card.querySelector('.category').textContent.toLowerCase();
      if (description.includes(searchTerm) || category.includes(searchTerm)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function deleteCard(button) {
    if (isLoading) return;

    const id = button.getAttribute('data-id');
    const confirmAction = confirm("Are you sure you want to delete this expense?");
    if (!confirmAction) return;

    showPageLoading(); // Show loading overlay

    fetch(`/expenses/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.querySelector(`.expense-card[data-id="${id}"]`)?.remove();
          showConfirmationMessage('Expense deleted successfully!', true);
        } else {
          showConfirmationMessage(data.message || 'Failed to delete expense.', false);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showConfirmationMessage('An error occurred while deleting the expense.', false);
      })
      .finally(() => {
        hidePageLoading(); // Hide loading overlay
      });
  }
</script>
@endsection