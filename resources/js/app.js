import './bootstrap';

// Import jQuery
import $ from 'jquery';
window.$ = window.jQuery = $;

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
window.tippy = tippy;

import 'datatables.net';
import 'datatables.net-bs5'; // If using Bootstrap 5

function updateAllocationLimits() {
    const totalBudget = Number(document.getElementById('totalBudget').value) || 0;
    const categoryAllocations = document.querySelectorAll('.category-allocation');
    const allocationRanges = document.querySelectorAll('.allocation-range');
    const allocationSummary = document.querySelector('.allocation-summary');
    const saveBudgetBtn = document.getElementById('saveBudget');
    
    // Calculate total allocated amount
    const totalAllocated = Array.from(categoryAllocations)
        .reduce((sum, input) => sum + Number(input.value || 0), 0);
    const remainingBudget = totalBudget - totalAllocated;
    
    // Update max values for sliders based on remaining budget
    allocationRanges.forEach(range => {
        const categoryId = range.dataset.categoryId;
        const currentInput = document.querySelector(`.category-allocation[data-category-id="${categoryId}"]`);
        const currentAmount = Number(currentInput.value) || 0;
        
        // Calculate max percentage based on remaining budget + current amount
        const maxAmount = remainingBudget + currentAmount;
        const maxPercentage = totalBudget > 0 ? (maxAmount / totalBudget) * 100 : 0;
        
        // Set max value for the slider
        range.max = Math.min(100, maxPercentage);
    });
    
    // Update allocation displays and progress bars
    categoryAllocations.forEach(input => {
        updateAllocationFromInput(input);
    });
    
    // Update summary
    updateAllocationSummary();
    
    // Validate total budget
    if (!totalBudget || totalBudget <= 0) {
        showError('totalBudget', 'Please enter a valid budget amount');
        allocationSummary.classList.add('error');
        if (saveBudgetBtn) {
            saveBudgetBtn.disabled = true;
            saveBudgetBtn.classList.add('disabled');
        }
        // Disable all category inputs and sliders
        categoryAllocations.forEach(input => {
            input.disabled = true;
        });
        allocationRanges.forEach(range => {
            range.disabled = true;
        });
    } else {
        clearError('totalBudget');
        allocationSummary.classList.remove('error');
        if (saveBudgetBtn) {
            saveBudgetBtn.disabled = false;
            saveBudgetBtn.classList.remove('disabled');
        }
        // Enable all category inputs and sliders
        categoryAllocations.forEach(input => {
            input.disabled = false;
        });
        allocationRanges.forEach(range => {
            range.disabled = false;
        });
    }
}

function updateAllocationFromInput(input) {
    const categoryId = input.dataset.categoryId;
    const amount = Number(input.value) || 0;
    const totalBudget = Number(document.getElementById('totalBudget').value) || 0;
    const categoryItem = input.closest('.category-budget-item');
    
    // Calculate total allocated amount excluding current category
    const totalAllocated = Array.from(document.querySelectorAll('.category-allocation'))
        .reduce((sum, input) => {
            if (input.dataset.categoryId !== categoryId) {
                return sum + Number(input.value || 0);
            }
            return sum;
        }, 0);
    
    // Calculate remaining budget
    const remainingBudget = totalBudget - totalAllocated;
    
    // Update range slider
    const range = document.querySelector(`.allocation-range[data-category-id="${categoryId}"]`);
    const percentage = totalBudget > 0 ? (amount / totalBudget) * 100 : 0;
    
    // Ensure percentage doesn't exceed the limit based on remaining budget
    const maxPercentage = totalBudget > 0 ? ((remainingBudget + amount) / totalBudget) * 100 : 0;
    const cappedPercentage = Math.min(percentage, maxPercentage);
    
    // If percentage is capped, update the input value to match
    if (percentage > maxPercentage) {
        input.value = Math.round((maxPercentage / 100) * totalBudget);
    }
    
    // Update max value for the slider
    range.max = Math.min(100, maxPercentage);
    
    // Smoothly update the range value
    requestAnimationFrame(() => {
        range.value = cappedPercentage;
        // Trigger the input event to update the slider UI
        range.dispatchEvent(new Event('input', { bubbles: true }));
    });
    
    // Update percentage display
    const percentageDisplay = range.parentElement.querySelector('.percentage');
    percentageDisplay.textContent = `${Math.round(cappedPercentage)}%`;
    
    // Update amount display
    const amountDisplay = categoryItem.querySelector('.category-allocation-display');
    amountDisplay.textContent = amount.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    
    // Update progress bar
    const progressFill = categoryItem.querySelector('.progress-fill');
    const progressPercent = categoryItem.querySelector('.stat-percent');
    
    // Add updating class for animation
    progressFill.classList.add('updating');
    requestAnimationFrame(() => {
        progressFill.style.width = `${cappedPercentage}%`;
        progressPercent.textContent = `${Math.round(cappedPercentage)}%`;
    });
    
    // Remove updating class after animation
    setTimeout(() => {
        progressFill.classList.remove('updating');
    }, 500);
    
    // Update remaining amount for this category
    const remainingDisplay = categoryItem.querySelector('.stat-remaining');
    const remaining = totalBudget - (totalAllocated + amount);
    remainingDisplay.textContent = `₱${remaining.toLocaleString('en-PH', { minimumFractionDigits: 2 })} left`;
    
    // Update color based on allocation
    if (totalAllocated + amount > totalBudget) {
        progressFill.style.backgroundColor = 'var(--danger-color)';
        remainingDisplay.style.color = 'var(--danger-color)';
        percentageDisplay.classList.add('error');
        categoryItem.classList.add('error');
        showError(`category-${categoryId}`, 'Allocation exceeds total budget');
    } else if (totalAllocated + amount > totalBudget * 0.9) {
        progressFill.style.backgroundColor = 'var(--warning-color)';
        remainingDisplay.style.color = 'var(--warning-color)';
        percentageDisplay.classList.add('warning');
        categoryItem.classList.remove('error');
        clearError(`category-${categoryId}`);
    } else {
        progressFill.style.backgroundColor = 'var(--primary-color)';
        remainingDisplay.style.color = 'var(--text-primary)';
        percentageDisplay.classList.remove('warning', 'error');
        categoryItem.classList.remove('error');
        clearError(`category-${categoryId}`);
    }
    
    // Update the allocation summary
    updateAllocationSummary();
}

function updateAllocationSummary() {
    const totalBudget = Number(document.getElementById('totalBudget').value) || 0;
    const categoryAllocations = document.querySelectorAll('.category-allocation');
    const totalAllocated = Array.from(categoryAllocations)
        .reduce((sum, input) => sum + Number(input.value), 0);
    const remaining = totalBudget - totalAllocated;
    const allocationSummary = document.querySelector('.allocation-summary');
    
    document.getElementById('totalAllocated').textContent = `₱${totalAllocated.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
    document.getElementById('remainingAllocation').textContent = `₱${remaining.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
    
    // Update visual feedback
    const remainingElement = document.getElementById('remainingAllocation');
    if (remaining < 0) {
        remainingElement.style.color = 'var(--danger-color)';
        allocationSummary.classList.add('error');
        allocationSummary.classList.remove('warning');
    } else if (remaining < totalBudget * 0.1) {
        remainingElement.style.color = 'var(--warning-color)';
        allocationSummary.classList.add('warning');
        allocationSummary.classList.remove('error');
    } else {
        remainingElement.style.color = 'var(--text-primary)';
        allocationSummary.classList.remove('warning', 'error');
    }
}

function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    let errorMessage = element.parentElement.querySelector('.error-message');
    if (!errorMessage) {
        errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        element.parentElement.appendChild(errorMessage);
    } else {
        errorMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    // Show error message with animation
    requestAnimationFrame(() => {
        errorMessage.classList.add('show');
    });
}

function clearError(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const errorMessage = element.parentElement.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.classList.remove('show');
        setTimeout(() => {
            errorMessage.remove();
        }, 300);
    }
}

function updateAllocationFromRange(range) {
    const categoryId = range.dataset.categoryId;
    const percentage = Number(range.value);
    const totalBudget = Number(document.getElementById('totalBudget').value) || 0;
    const amount = Math.round((percentage / 100) * totalBudget);
    
    const input = document.querySelector(`.category-allocation[data-category-id="${categoryId}"]`);
    if (!input) return;
    
    // Update input value
    input.value = amount;
    
    // Update the UI
    updateAllocationFromInput(input);
}

// Initialize budget controls when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const totalBudgetInput = document.getElementById('totalBudget');
    const categoryAllocations = document.querySelectorAll('.category-allocation');
    const allocationRanges = document.querySelectorAll('.allocation-range');
    const saveBudgetBtn = document.getElementById('saveBudget');
    const budgetSetter = document.getElementById('budgetSetter');
    const budgetDashboard = document.getElementById('budgetDashboard');
    
    if (totalBudgetInput) {
        totalBudgetInput.addEventListener('input', updateAllocationLimits);
        // Initial validation
        updateAllocationLimits();
    }
    
    categoryAllocations.forEach(input => {
        input.addEventListener('input', () => {
            // Prevent negative values
            if (Number(input.value) < 0) {
                input.value = 0;
            }
            updateAllocationFromInput(input);
        });
    });
    
    allocationRanges.forEach(range => {
        let isDragging = false;
        
        range.addEventListener('mousedown', function() {
            isDragging = true;
            this.classList.add('active');
        });
        
        range.addEventListener('mouseup', function() {
            isDragging = false;
            this.classList.remove('active');
        });
        
        range.addEventListener('mouseleave', function() {
            if (isDragging) {
                isDragging = false;
                this.classList.remove('active');
            }
        });
        
        range.addEventListener('input', function(e) {
            updateAllocationFromRange(this);
        });
    });
    
    if (saveBudgetBtn) {
        saveBudgetBtn.addEventListener('click', async function() {
            const totalBudget = Number(totalBudgetInput.value);
            const categoryAllocations = document.querySelectorAll('.category-allocation');
            const totalAllocated = Array.from(categoryAllocations)
                .reduce((sum, input) => sum + Number(input.value || 0), 0);
            
            // Validate total budget
            if (!totalBudget || totalBudget <= 0) {
                showNotification('Please enter a valid budget amount', 'error');
                saveBudgetBtn.classList.add('error');
                setTimeout(() => saveBudgetBtn.classList.remove('error'), 1000);
                return;
            }
            
            // Validate allocations
            if (totalAllocated <= 0) {
                showNotification('Please allocate amounts to at least one category', 'error');
                saveBudgetBtn.classList.add('error');
                setTimeout(() => saveBudgetBtn.classList.remove('error'), 1000);
                return;
            }
            
            if (totalAllocated > totalBudget) {
                showNotification('Total category allocations cannot exceed total budget', 'error');
                saveBudgetBtn.classList.add('error');
                setTimeout(() => saveBudgetBtn.classList.remove('error'), 1000);
                return;
            }

            // Disable all inputs and show loading state
            saveBudgetBtn.classList.add('loading');
            saveBudgetBtn.disabled = true;
            categoryAllocations.forEach(input => {
                input.disabled = true;
            });
            allocationRanges.forEach(range => {
                range.disabled = true;
            });
            totalBudgetInput.disabled = true;

            const budgets = Array.from(categoryAllocations).map(input => ({
                category_id: parseInt(input.dataset.categoryId),
                amount_limit: Number(input.value)
            }));

            const requestData = {
                month: document.getElementById('currentMonth').value,
                amount_limit: totalBudget,
                budgets: budgets
            };

            try {
                const response = await fetch("/budgets", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(requestData)
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Budget and allocations saved successfully!', 'success');
                    // Add success animation
                    saveBudgetBtn.classList.remove('loading');
                    saveBudgetBtn.classList.add('success');
                    
                    // Hide budget setter and show dashboard immediately
                    if (budgetSetter) budgetSetter.style.display = 'none';
                    if (budgetDashboard) budgetDashboard.style.display = 'block';
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Error saving budgets');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification(error.message || 'An error occurred while saving budgets', 'error');
                // Add error animation
                saveBudgetBtn.classList.remove('loading');
                saveBudgetBtn.classList.add('error');
                setTimeout(() => {
                    saveBudgetBtn.classList.remove('error');
                    saveBudgetBtn.disabled = false;
                    categoryAllocations.forEach(input => {
                        input.disabled = false;
                    });
                    allocationRanges.forEach(range => {
                        range.disabled = false;
                    });
                    totalBudgetInput.disabled = false;
                }, 1000);
            }
        });
    }
});