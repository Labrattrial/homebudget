@extends('layouts.main')

@section('content')
@vite(['resources/css/expenses.css', 'resources/js/fontawesome.js'])

<style>
  .custom-confirmation {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 9999;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }

  .custom-confirmation.show {
    opacity: 1;
    transform: translateY(0);
  }

  .custom-confirmation.success {
    background-color: #4CAF50;
    color: white;
  }

  .custom-confirmation.error {
    background-color: #f44336;
    color: white;
  }

  .custom-confirmation .icon {
    font-size: 20px;
  }

  .custom-confirmation .message {
    font-size: 14px;
  }

  .expense-card {
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  
  .expense-card.fade-out {
    opacity: 0;
    transform: translateX(20px);
  }

  .save-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    background-color: #4CAF50;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .save-btn:hover:not(:disabled) {
    background-color: #45a049;
  }

  .save-btn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
    opacity: 0.7;
  }

  .save-btn .spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 0.8s ease-in-out infinite;
    display: none;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .modal-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(2px);
    flex-direction: column;
    gap: 15px;
  }

  .modal-loading.show {
    display: flex;
  }

  .modal-loading .spinner {
    width: 50px;
    height: 50px;
    position: relative;
  }

  .modal-loading .loading-text {
    color: #4CAF50;
    font-size: 16px;
    font-weight: 500;
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
  }

  .modal-loading .spinner:before,
  .modal-loading .spinner:after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #4CAF50;
  }

  .modal-loading .spinner:before {
    animation: spin 1.5s linear infinite;
  }

  .modal-loading .spinner:after {
    border-top-color: #45a049;
    animation: spin 2s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
</style>

<div class="expenses-wrapper">
  <!-- Summary Section -->
  <div class="expenses-summary">
    <div class="summary-card total-expenses">
      <h3>Total Expenses</h3>
      <p class="amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($expenses->sum('amount'), 2) }}</p>
      <p class="period">This Month</p>
    </div>
    <div class="summary-card category-breakdown">
      <h3>Category Breakdown</h3>
      <div class="category-list">
        @foreach($categories as $category)
          @php
            $categoryTotal = $expenses->where('category_id', $category->id)->sum('amount');
            $percentage = $expenses->sum('amount') > 0 ? 
              ($categoryTotal / $expenses->sum('amount')) * 100 : 0;
          @endphp
          <div class="category-item">
            <span class="category-name">{{ $category->name }}</span>
            <div class="category-bar">
              <div class="bar-fill" style="width: {{ $percentage }}%"></div>
            </div>
            <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($categoryTotal, 2) }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- Header with title, filter, search bar, and add button -->
  <div class="expenses-header">
    <h2>Recent Expenses</h2>
    <div class="header-actions">
      <input type="month" id="monthFilter" value="{{ now()->format('Y-m') }}" onchange="filterByMonth(this.value)" />
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search by description or category" oninput="searchExpenses()" />
      </div>
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
        <p><strong>Total Expense:</strong> <span class="amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>{{ number_format($expense->amount, 2) }}</span></p>
        <p><strong>Date:</strong> <span class="date">{{ \Carbon\Carbon::parse($expense->date)->format('m/d/Y') }}</span></p>
      </div>
    @endforeach
  </div>

  <!-- Modal -->
  <div id="expenseModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
    <div class="modal-content">
      <button class="close" onclick="closeModal()" aria-label="Close modal">&times;</button>
      <h2 id="modalTitle">
        <i class="fas fa-plus-circle"></i>
        <span>Add New Expense</span>
      </h2>
      <div class="modal-loading">
        <div class="spinner"></div>
        <div class="loading-text">Loading...</div>
      </div>
      <form id="expenseForm" novalidate>
        @csrf
        <div class="form-group">
          <label for="modalCategory">Category:</label>
          <div class="custom-select">
            <select id="modalCategory" name="category_id" required onchange="validateField(this)">
              <option value="" disabled selected>Select a category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="error-message" id="error-category_id"></div>
        </div>

        <div class="form-group">
          <label>Specs:</label>
          <div class="specs-options">
            <input type="radio" id="existingSpecs" name="specs_option" value="existing" checked onclick="toggleSpecsInput('existing')" />
            <label for="existingSpecs">Select Existing</label>
            
            <input type="radio" id="newSpecs" name="specs_option" value="new" onclick="toggleSpecsInput('new')" />
            <label for="newSpecs">Add New</label>
          </div>

          <div id="existingSpecsContainer" class="form-group">
            <div class="custom-select">
              <select id="modalSpecs" name="description" required onchange="validateField(this)">
                <option value="" disabled selected>Select Specifics</option>
                @foreach($specs as $spec)
                  <option value="{{ $spec }}">{{ $spec }}</option>
                @endforeach
              </select>
            </div>
            <div class="error-message" id="error-description"></div>
          </div>

          <div id="newSpecsContainer" class="form-group" style="display: none;">
            <input type="text" id="newSpecsInput" name="new_specs" placeholder="Enter new specs (optional)" />
            <div class="error-message" id="error-new_specs"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="modalAmount">Total Expense:</label>
          <div class="input-field">
            <span class="currency">{{ Auth::user()->currency_symbol }}</span>
          <input type="number" name="amount" id="modalAmount" required placeholder="Enter amount" step="0.01" min="0.01" onchange="validateField(this)" />
          </div>
          <div class="error-message" id="error-amount"></div>
        </div>

        <div class="form-group">
          <label for="modalDate">Date:</label>
          <input type="date" name="date" id="modalDate" required onchange="validateField(this)" max="{{ date('Y-m-d') }}" />
          <div class="error-message" id="error-date"></div>
        </div>

        <button type="submit" class="save-btn" id="submitButton">
          <span id="buttonText">Save</span>
          <div class="spinner" style="display: none;"></div>
        </button>
      </form>
    </div>
  </div>

  <!-- Custom Confirmation Message -->
  <div id="customConfirmation" class="custom-confirmation" role="alert" aria-live="polite">
    <div class="icon">
      <i class="fas fa-check"></i>
    </div>
    <div class="message"></div>
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

  function showCustomConfirmation(message, success = true) {
    try {
      // Create confirmation element if it doesn't exist
      let confirmation = document.getElementById('customConfirmation');
      if (!confirmation) {
        confirmation = document.createElement('div');
        confirmation.id = 'customConfirmation';
        confirmation.className = 'custom-confirmation';
        confirmation.setAttribute('role', 'alert');
        confirmation.setAttribute('aria-live', 'polite');
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'icon';
        const icon = document.createElement('i');
        icon.className = success ? 'fas fa-check' : 'fas fa-times';
        iconDiv.appendChild(icon);
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.textContent = message;
        
        confirmation.appendChild(iconDiv);
        confirmation.appendChild(messageDiv);
        document.body.appendChild(confirmation);
      } else {
        // Update existing confirmation
        const icon = confirmation.querySelector('.icon i');
        const messageEl = confirmation.querySelector('.message');
        
        if (icon) {
          icon.className = success ? 'fas fa-check' : 'fas fa-times';
        }
        
        if (messageEl) {
          messageEl.textContent = message;
        }
      }
      
      // Update classes
      confirmation.className = 'custom-confirmation ' + (success ? 'success' : 'error');
      
      // Show confirmation
      confirmation.classList.add('show');
      
      // Hide after 3 seconds
      setTimeout(() => {
        confirmation.classList.remove('show');
      }, 3000);
    } catch (error) {
      console.error('Error showing confirmation:', error);
      // Fallback to alert if confirmation message fails
      alert(message);
    }
  }

  function showModalLoading(show) {
    const loadingEl = document.querySelector('.modal-loading');
    if (show) {
      loadingEl.classList.add('show');
    } else {
      loadingEl.classList.remove('show');
    }
  }

  async function fetchSpecsByCategory(categoryId) {
    showModalLoading(true);
    try {
      const response = await fetch(`/expenses/categories/${categoryId}/descriptions`, {
        headers: { 'Accept': 'application/json' },
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      const specsDropdown = document.getElementById('modalSpecs');
      specsDropdown.innerHTML = '<option value="" disabled selected>Select specs</option>';
      
      if (data.specs && Array.isArray(data.specs)) {
        data.specs.forEach(spec => {
          if (spec) { // Only add non-null/undefined specs
            specsDropdown.insertAdjacentHTML('beforeend', `<option value="${spec}">${spec}</option>`);
          }
        });
      }
    } catch (error) {
      console.error('Error fetching specs:', error);
      showCustomConfirmation('Error loading specs', false);
    } finally {
      showModalLoading(false);
    }
  }

  function toggleLoading(state, button = null) {
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.querySelector('.spinner');

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

  function openModal() {
    if (isLoading) return;
    editingId = null;
    document.getElementById("modalTitle").innerText = "Add New Expense";
    document.getElementById("modalCategory").value = '';
    document.getElementById("modalAmount").value = '';
    
    // Set current date in the date picker
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${year}-${month}-${day}`;
    
    // Set the date picker value and max date
    const datePicker = document.getElementById("modalDate");
    datePicker.value = todayFormatted;
    datePicker.max = todayFormatted;
    
    document.getElementById("modalSpecs").value = '';
    document.getElementById("newSpecsInput").value = '';
    document.getElementById("expenseModal").style.display = "flex";
    document.getElementById("expenseModal").setAttribute('aria-hidden', 'false');
    // Focus the first input when modal opens
    setTimeout(() => {
      document.getElementById("modalCategory").focus();
    }, 100);
  }

  function closeModal() {
    if (isLoading) return;
    const modal = document.getElementById("expenseModal");
    modal.style.display = "none";
    // Remove aria-hidden after a short delay to ensure focus is properly managed
    setTimeout(() => {
      modal.setAttribute('aria-hidden', 'true');
    }, 100);
  }

  function toggleSpecsInput(option) {
    const existingContainer = document.getElementById('existingSpecsContainer');
    const newContainer = document.getElementById('newSpecsContainer');
    const existingSelect = document.getElementById('modalSpecs');
    const newInput = document.getElementById('newSpecsInput');

    if (option === 'existing') {
      existingContainer.style.display = 'block';
      newContainer.style.display = 'none';
      existingSelect.required = true;
      newInput.required = false;
      newInput.value = ''; // Clear new input when switching
    } else {
      existingContainer.style.display = 'none';
      newContainer.style.display = 'block';
      existingSelect.required = false;
      newInput.required = true; // Make new input required when "Add New" is selected
      existingSelect.value = ''; // Clear select when switching
    }
  }

  function validateField(field) {
    const formGroup = field.closest('.form-group');
    const errorElement = formGroup.querySelector('.error-message');
    
    // Remove existing validation classes
    formGroup.classList.remove('error', 'success');
    errorElement.textContent = '';

    // Handle new specs input validation
    if (field.id === 'newSpecsInput') {
      const newSpecsRadio = document.getElementById('newSpecs');
      if (newSpecsRadio.checked && !field.value.trim()) {
        formGroup.classList.add('error');
        errorElement.textContent = 'Please enter new specs';
        return false;
      }
      return true;
    }

    // Validate required fields
    if (field.required && !field.value) {
      formGroup.classList.add('error');
      errorElement.textContent = 'This field is required';
      return false;
    }

    // Validate amount
    if (field.id === 'modalAmount') {
      const amount = parseFloat(field.value);
      if (isNaN(amount) || amount <= 0) {
        formGroup.classList.add('error');
        errorElement.textContent = 'Please enter a valid amount greater than 0';
        return false;
      }
    }

    // Validate date
    if (field.type === 'date') {
      const selectedDate = new Date(field.value);
      const today = new Date();
      today.setHours(23, 59, 59, 999); // Set to end of current day for comparison
      
      if (selectedDate > today) {
        formGroup.classList.add('error');
        errorElement.textContent = 'Date cannot be in the future';
        return false;
      }
    }

    // If validation passes
    formGroup.classList.add('success');
    return true;
  }

  function validateForm() {
    const form = document.getElementById('expenseForm');
    const fields = form.querySelectorAll('input[required], select[required]');
    let isValid = true;

    // Check which specs option is selected
    const specsOption = document.querySelector('input[name="specs_option"]:checked');
    const isNewSpecs = specsOption && specsOption.value === 'new';

    fields.forEach(field => {
      // Skip validation for new specs input if it's not being used
      if (field.id === 'newSpecsInput' && !isNewSpecs) {
        return;
      }
      if (!validateField(field)) {
        isValid = false;
      }
    });

    // Additional validation for new specs input when selected
    if (isNewSpecs) {
      const newSpecsInput = document.getElementById('newSpecsInput');
      if (!validateField(newSpecsInput)) {
        isValid = false;
      }
    }

    return isValid;
  }

  document.getElementById("expenseForm").onsubmit = async function(event) {
    event.preventDefault();
    if (isLoading) return;

    if (!validateForm()) {
      showCustomConfirmation('Please check the form for errors', false);
      return;
    }

    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const spinner = submitButton.querySelector('.spinner');
    const modal = document.getElementById("expenseModal");
    
    // Show loading state
    isLoading = true;
    submitButton.disabled = true;
    buttonText.style.display = 'none';
    spinner.style.display = 'block';
    showModalLoading(true);

    try {
      const formData = new FormData(this);
      
      // Only include specs_option if one is selected
      const specsOption = document.querySelector('input[name="specs_option"]:checked');
      if (!specsOption) {
        formData.delete('specs_option');
        formData.delete('description');
        formData.delete('new_specs');
      }

      if (editingId) {
        formData.append('_method', 'PUT');
      }

      const url = editingId ? `/expenses/${editingId}` : "{{ route('user.expenses.store') }}";
      const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
        },
      });
      
      const data = await response.json();
      
      if (data.success) {
        showCustomConfirmation('Expense saved successfully!', true);
        
        // Reset form and close modal
        this.reset();
        modal.style.display = "none";
        modal.setAttribute('aria-hidden', 'true');
        
        if (data.expense) {
          if (editingId) {
            // Update existing card
            const existingCard = document.querySelector(`.expense-card[data-id="${editingId}"]`);
            if (existingCard) {
              const updatedCard = createExpenseCard(data.expense);
              existingCard.replaceWith(updatedCard);
            }
          } else {
            // Add new card
            const newExpenseCard = createExpenseCard(data.expense);
            document.getElementById('recentExpensesGrid').prepend(newExpenseCard);
          }
        }
        
        // Update summary if data is available
        if (data.totalExpenses !== undefined && data.categoryBreakdown) {
          updateSummaryAfterDelete(data.totalExpenses, data.categoryBreakdown);
        }

        // Reset editingId after successful save
        editingId = null;
      } else {
        showValidationErrors(data.errors || {});
        showCustomConfirmation('Please check the form for errors', false);
      }
    } catch (error) {
      console.error('Error:', error);
      showCustomConfirmation('An error occurred while saving the expense', false);
    } finally {
      // Reset loading state
      isLoading = false;
      submitButton.disabled = false;
      buttonText.style.display = 'block';
      spinner.style.display = 'none';
      showModalLoading(false);
    }
  };

  function createExpenseCard(expense) {
    const card = document.createElement('div');
    card.className = 'expense-card';
    card.setAttribute('data-id', expense.id);
    card.setAttribute('data-category', expense.category_id);
    
    // Format the date to show actual numbers
    const date = new Date(expense.date);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    const formattedDate = `${month}/${day}/${year}`;
    
    card.innerHTML = `
      <div class="expense-icons">
        <i class="fas fa-edit edit-icon" onclick="editCard(this)" title="Edit" data-id="${expense.id}"></i>
        <i class="fas fa-trash delete-icon" onclick="deleteCard(this)" title="Delete" data-id="${expense.id}"></i>
      </div>
      <p><strong>Category:</strong> <span class="category">${expense.category.name}</span></p>
      <p><strong>Specs:</strong> <span class="specs">${expense.description}</span></p>
      <p><strong>Total Expense:</strong> <span class="amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>${parseFloat(expense.amount).toFixed(2)}</span></p>
      <p><strong>Date:</strong> <span class="date">${formattedDate}</span></p>
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

  async function editCard(button) {
    if (isLoading) return;
    
    const id = button.getAttribute('data-id');
    const card = button.closest('.expense-card');
    
    // Get all required data at once
    const category = card.querySelector('.category').textContent;
    const specs = card.querySelector('.specs').textContent;
    const amount = card.querySelector('.amount').textContent.replace(/[₱,]/g, '');
    const dateText = card.querySelector('.date').textContent;

    // Set modal title and ID immediately
    editingId = id;
    document.getElementById("modalTitle").innerText = "Edit Expense";
    
    // Get form elements once
    const categorySelect = document.getElementById("modalCategory");
    const specsSelect = document.getElementById("modalSpecs");
    const existingSpecsRadio = document.getElementById('existingSpecs');
    const newSpecsRadio = document.getElementById('newSpecs');
    const existingSpecsContainer = document.getElementById('existingSpecsContainer');
    const newSpecsContainer = document.getElementById('newSpecsContainer');
    const newSpecsInput = document.getElementById('newSpecsInput');
    const modalAmount = document.getElementById("modalAmount");
    const modalDate = document.getElementById("modalDate");
    const modal = document.getElementById("expenseModal");

    // Set category value
    Array.from(categorySelect.options).forEach(option => {
      if (option.text === category) {
        categorySelect.value = option.value;
      }
    });

    // Show modal immediately
    modal.style.display = "flex";
    modal.setAttribute('aria-hidden', 'false');

    // Set amount and date immediately
    modalAmount.value = amount;
    
    // Convert MM/DD/YYYY to YYYY-MM-DD for the date picker
    const [month, day, year] = dateText.split('/');
    const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    modalDate.value = formattedDate;

    try {
      // Fetch specs in parallel with UI updates
      const specsPromise = fetchSpecsByCategory(categorySelect.value);
      
      // Check if specs exist in current options
      const specsExists = Array.from(specsSelect.options).some(option => option.text === specs);
      
      if (specsExists) {
        existingSpecsRadio.checked = true;
        existingSpecsContainer.style.display = 'block';
        newSpecsContainer.style.display = 'none';
        specsSelect.value = specs;
        newSpecsInput.value = '';
      } else {
        newSpecsRadio.checked = true;
        existingSpecsContainer.style.display = 'none';
        newSpecsContainer.style.display = 'block';
        newSpecsInput.value = specs;
        specsSelect.value = '';
      }

      // Wait for specs to load
      await specsPromise;
      
      // Update specs select with new options if needed
      if (!specsExists) {
        const newSpecsExists = Array.from(specsSelect.options).some(option => option.text === specs);
        if (newSpecsExists) {
          existingSpecsRadio.checked = true;
          existingSpecsContainer.style.display = 'block';
          newSpecsContainer.style.display = 'none';
          specsSelect.value = specs;
          newSpecsInput.value = '';
        }
      }
    } catch (error) {
      console.error('Error in editCard:', error);
      showCustomConfirmation('Error loading expense details', false);
    }
  }

  async function deleteCard(button) {
    if (isLoading) return;

    const id = button.getAttribute('data-id');
    const card = button.closest('.expense-card');
    
    if (!card) {
      console.error('Card element not found');
      return;
    }

    const confirmAction = confirm("Are you sure you want to delete this expense?");
    if (!confirmAction) return;

    // Add loading state to the button
    button.classList.add('loading');
    isLoading = true;

    try {
      const response = await fetch(`/expenses/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      
      if (data.success) {
        // Add animation class
        card.classList.add('fade-out');
        
        // Wait for animation to complete before removing
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Remove the card
        card.remove();
        
        // Update total expenses and category breakdown
        if (data.totalExpenses !== undefined && data.categoryBreakdown) {
          updateSummaryAfterDelete(data.totalExpenses, data.categoryBreakdown);
        }
        
        showCustomConfirmation('Expense deleted successfully!', true);
      } else {
        throw new Error(data.message || 'Failed to delete expense');
      }
    } catch (error) {
      console.error('Error:', error);
      showCustomConfirmation(error.message || 'An error occurred while deleting the expense.', false);
    } finally {
      button.classList.remove('loading');
      isLoading = false;
    }
  }

  function updateSummaryAfterDelete(totalExpenses, categoryBreakdown) {
    // Update total expenses
    document.querySelector('.total-expenses .amount').textContent = 
      '<span class="currency">{{ Auth::user()->currency_symbol }}</span>' + parseFloat(totalExpenses).toFixed(2);

    // Update category breakdown
    const categoryList = document.querySelector('.category-list');
    categoryList.innerHTML = '';
    categoryBreakdown.forEach(category => {
      const percentage = totalExpenses > 0 ? 
        (category.total / totalExpenses) * 100 : 0;
      
      categoryList.innerHTML += `
        <div class="category-item">
          <span class="category-name">${category.name}</span>
          <div class="category-bar">
            <div class="bar-fill" style="width: ${percentage}%"></div>
          </div>
          <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>${parseFloat(category.total).toFixed(2)}</span>
        </div>
      `;
    });
  }

  function filterByMonth(month) {
    showPageLoading();
    fetch(`/expenses?month=${month}`, {
      headers: { 'Accept': 'application/json' },
    })
    .then(response => response.json())
    .then(data => {
      // Update the expenses grid
      const grid = document.getElementById('recentExpensesGrid');
      grid.innerHTML = '';
      data.expenses.forEach(expense => {
        grid.appendChild(createExpenseCard(expense));
      });

      // Update the summary section
      document.querySelector('.total-expenses .amount').textContent = 
        '<span class="currency">{{ Auth::user()->currency_symbol }}</span>' + parseFloat(data.totalExpenses).toFixed(2);

      // Update category breakdown
      const categoryList = document.querySelector('.category-list');
      categoryList.innerHTML = '';
      data.categoryBreakdown.forEach(category => {
        const percentage = data.totalExpenses > 0 ? 
          (category.total / data.totalExpenses) * 100 : 0;
        
        categoryList.innerHTML += `
          <div class="category-item">
            <span class="category-name">${category.name}</span>
            <div class="category-bar">
              <div class="bar-fill" style="width: ${percentage}%"></div>
            </div>
            <span class="category-amount"><span class="currency">{{ Auth::user()->currency_symbol }}</span>${parseFloat(category.total).toFixed(2)}</span>
          </div>
        `;
      });
    })
    .catch(error => {
      console.error('Error:', error);
      showCustomConfirmation('Error loading expenses', false);
    })
    .finally(() => {
      hidePageLoading();
    });
  }
</script>
@endsection