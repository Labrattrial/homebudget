@extends('layouts.main')

@section('content')
@vite(['resources/css/settings.css', 'resources/js/fontawesome.js'])

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Notification Card -->
<div id="notificationCard" class="notification-card" style="display: none;">
    <div class="notification-content">
        <i class="fas fa-check-circle notification-icon"></i>
        <div class="notification-message"></div>
    </div>
    <button class="notification-close">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Processing...</span>
    </div>
</div>

<!-- Confirmation Dialog -->
<div id="confirmationDialog" class="confirmation-dialog">
    <div class="dialog-content">
        <h3 id="dialogTitle">Confirm Action</h3>
        <p id="dialogMessage">Are you sure you want to perform this action?</p>
        <div class="dialog-buttons">
            <button id="dialogCancel" class="btn btn-light">Cancel</button>
            <button id="dialogConfirm" class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>

<div class="settings-container">
    <div class="settings-card">
        <h2 class="settings-header">Settings</h2>

        {{-- Profile Picture Form --}}
<form id="profileForm" enctype="multipart/form-data">
    @csrf
    <div class="profile-picture-container">
        <label for="profile_picture" class="profile-picture-label">
            <div class="profile-picture-wrapper">
                        <img src="{{ Auth::user()->profile_picture_url }}" 
                        alt="Profile Picture" 
                        class="profile-picture"
                        id="currentProfilePicture">
                <div class="profile-picture-preview" id="profilePicturePreview" style="display: none;"></div>
                <div class="profile-picture-loading" id="profilePictureLoading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
            <p class="profile-picture-text">Tap to change profile picture</p>
        </label>
        <input type="file" name="profile_picture" id="profile_picture" style="display: none;" accept="image/*">
        
        <div class="profile-picture-actions" id="profilePictureActions" style="display: none;">
            <button type="button" class="btn btn-light" id="cancelProfileUpdate">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="confirmProfileUpdate">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
        </form>

        <div class="divider"></div>

        {{-- Currency Selection Form --}}
        <form id="currencyForm" action="{{ route('settings.currency.update') }}" method="POST">
            @csrf
            <h3 class="section-header">
                <i class="fas fa-money-bill-wave"></i>
                Currency Settings
            </h3>
            <div class="form-group">
                <label for="currency" class="form-label">Select Currency</label>
                <div class="currency-grid">
                    @php
                        $currencies = \App\Helpers\CurrencyHelper::getAllCurrencies();
                        $currentCurrency = Auth::user()->currency;
                    @endphp
                    
                    @foreach($currencies as $code)
                        @php
                            $currencyInfo = \App\Helpers\CurrencyHelper::getCurrencyInfo($code);
                        @endphp
                        <label class="currency-card {{ $currentCurrency === $code ? 'selected' : '' }}">
                            <input type="radio" 
                                   name="currency" 
                                   value="{{ $code }}" 
                                   {{ $currentCurrency === $code ? 'checked' : '' }}>
                            <div class="currency-content">
                                <span class="currency-symbol">{{ $currencyInfo['symbol'] }}</span>
                                <span class="currency-name">{{ $currencyInfo['name'] }}</span>
                                <span class="currency-code">{{ $code }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="currencySubmitBtn">
                    <span class="btn-text">Update Currency</span>
                    <span class="btn-spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </form>

        <div class="divider"></div>

        {{-- Password Change Form --}}
        <form action="{{ route('settings.password.update') }}" method="POST" id="passwordForm">
            @csrf
            <h3 class="section-header">
                <i class="fas fa-lock"></i>
                Change Password
            </h3>

            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" 
                       name="current_password" 
                       placeholder="Enter your current password"
                       class="form-control"
                       id="current_password"
                       required>
                <div class="validation-message" id="current-password-error"></div>
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <div class="password-field-container">
                    <input type="password" 
                           name="new_password" 
                           placeholder="Enter your new password"
                           class="form-control"
                           id="new_password" 
                           required>
                    <button type="button" class="password-toggle" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength-meter" class="strength-meter">
                    <div class="strength-bar" data-strength="0"></div>
                </div>
                <span class="strength-message">Password strength</span>
                <ul id="password-requirements">
                    <li><i class="fas fa-times"></i> At least 8 characters</li>
                    <li><i class="fas fa-times"></i> One uppercase letter</li>
                    <li><i class="fas fa-times"></i> One lowercase letter</li>
                    <li><i class="fas fa-times"></i> One number</li>
                    <li><i class="fas fa-times"></i> One special character</li>
                </ul>
                <div class="validation-message" id="new-password-error"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-field-container">
                    <input type="password" 
                           name="new_password_confirmation"
                           placeholder="Confirm your new password"
                           class="form-control"
                           id="confirm_password"
                           required>
                    <button type="button" class="password-toggle" id="toggle-confirm-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="validation-message" id="confirm-password-error"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="passwordSubmitBtn">
                    <span class="btn-text">Update Password</span>
                    <span class="btn-spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Server-side toast messages
const serverMessages = {
    success: JSON.parse('@json(session("success"))'),
    error: JSON.parse('@json(session("error"))')
};

document.addEventListener('DOMContentLoaded', function() {
    // Password Toggle Functionality
    function setupToggle(passwordFieldId, toggleButtonId) {
        const passwordInput = document.getElementById(passwordFieldId);
        const toggleButton = document.getElementById(toggleButtonId);
        
        if (passwordInput && toggleButton) {
            toggleButton.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }

    // Password Strength Meter
    function checkPasswordStrength(password) {
        let strength = 0;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        // Count how many requirements are met
        Object.values(requirements).forEach(met => {
            if (met) strength++;
        });

        return {
            score: strength,
            requirements: requirements
        };
    }

    function updateStrengthMeter(password) {
        const result = checkPasswordStrength(password);
        const meter = document.querySelector('.strength-bar');
        const message = document.querySelector('.strength-message');
        const requirementsList = document.getElementById('password-requirements');
        
        // Update strength bar
        meter.style.width = `${(result.score / 5) * 100}%`;
        meter.dataset.strength = result.score;
        
        // Update strength message
        const messages = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'];
        message.textContent = messages[result.score - 1] || 'Very Weak';
        
        // Update requirements list
        requirementsList.innerHTML = `
            <li class="${result.requirements.length ? 'met' : ''}">
                <i class="fas ${result.requirements.length ? 'fa-check' : 'fa-times'}"></i>
                At least 8 characters
            </li>
            <li class="${result.requirements.uppercase ? 'met' : ''}">
                <i class="fas ${result.requirements.uppercase ? 'fa-check' : 'fa-times'}"></i>
                One uppercase letter
            </li>
            <li class="${result.requirements.lowercase ? 'met' : ''}">
                <i class="fas ${result.requirements.lowercase ? 'fa-check' : 'fa-times'}"></i>
                One lowercase letter
            </li>
            <li class="${result.requirements.number ? 'met' : ''}">
                <i class="fas ${result.requirements.number ? 'fa-check' : 'fa-times'}"></i>
                One number
            </li>
            <li class="${result.requirements.special ? 'met' : ''}">
                <i class="fas ${result.requirements.special ? 'fa-check' : 'fa-times'}"></i>
                One special character
            </li>
        `;
        
        // Update colors based on strength
        const colors = ['#ff4d4d', '#ff9966', '#ffcc00', '#99cc33', '#66cc33'];
        meter.style.backgroundColor = colors[result.score - 1] || colors[0];
    }

    // Client-side Validation
    function validatePasswordForm() {
        let isValid = true;
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const result = checkPasswordStrength(newPassword);

        // Clear previous errors
        document.querySelectorAll('.validation-message').forEach(el => {
            el.textContent = '';
        });

        // Current password validation
        if (!currentPassword) {
            document.getElementById('current-password-error').textContent = 'Current password is required';
            isValid = false;
        }

        // New password validation
        if (!newPassword) {
            document.getElementById('new-password-error').textContent = 'New password is required';
            isValid = false;
        } else if (result.score < 5) {
            document.getElementById('new-password-error').textContent = 'Password does not meet all requirements';
            isValid = false;
        }

        // Confirm password validation
        if (newPassword && confirmPassword && newPassword !== confirmPassword) {
            document.getElementById('confirm-password-error').textContent = 'Passwords do not match';
            isValid = false;
        }

        return isValid;
    }

    // Toast Notifications
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }, 100);
    }

    // Loading State Management
    function setLoadingState(formId, isLoading) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        const btnText = submitBtn.querySelector('.btn-text');
        const btnSpinner = submitBtn.querySelector('.btn-spinner');
        const overlay = document.getElementById('loadingOverlay');

        if (isLoading) {
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            overlay.style.display = 'flex';
            submitBtn.disabled = true;
        } else {
            btnText.style.display = 'inline-block';
            btnSpinner.style.display = 'none';
            overlay.style.display = 'none';
            submitBtn.disabled = false;
        }
    }

    // Confirmation Dialog System
    function showConfirmation(options) {
        return new Promise((resolve) => {
            const dialog = document.getElementById('confirmationDialog');
            const title = document.getElementById('dialogTitle');
            const message = document.getElementById('dialogMessage');
            const confirmBtn = document.getElementById('dialogConfirm');
            const cancelBtn = document.getElementById('dialogCancel');

            title.textContent = options.title || 'Confirm Action';
            message.textContent = options.message || 'Are you sure you want to perform this action?';
            confirmBtn.textContent = options.confirmText || 'Confirm';
            cancelBtn.textContent = options.cancelText || 'Cancel';

            dialog.style.display = 'flex';

            function handleConfirm() {
                dialog.style.display = 'none';
                resolve(true);
                removeListeners();
            }

            function handleCancel() {
                dialog.style.display = 'none';
                resolve(false);
                removeListeners();
            }

            function removeListeners() {
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
            }

            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
        });
    }

    // Initialize toggles
    setupToggle('new_password', 'toggle-password');
    setupToggle('confirm_password', 'toggle-confirm-password');

    // Initialize strength meter
    const passwordInput = document.getElementById('new_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updateStrengthMeter(this.value);
        });
    }

    // Form submission validation - Password Form
    document.getElementById('passwordForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (validatePasswordForm()) {
            const confirmed = await showConfirmation({
                title: 'Confirm Password Change',
                message: 'Are you sure you want to change your password?',
                confirmText: 'Change Password'
            });
            
            if (confirmed) {
                setLoadingState('passwordForm', true);
                this.submit();
            }
        }
    });

    // Profile Picture Elements
    const profileInput = document.getElementById('profile_picture');
    const previewContainer = document.getElementById('profilePicturePreview');
    const currentPicture = document.getElementById('currentProfilePicture');
    const profileIcon = document.getElementById('profileIcon');
    const loadingIndicator = document.getElementById('profilePictureLoading');
    const profileActions = document.getElementById('profilePictureActions');
    const cancelBtn = document.getElementById('cancelProfileUpdate');
    const confirmBtn = document.getElementById('confirmProfileUpdate');
    
    let selectedFile = null;

    // Profile Picture Change Handler
    if (profileInput) {
        profileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.match('image.*')) {
                showToast('Please select a valid image file', 'error');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showToast('Image size should not exceed 2MB', 'error');
                return;
            }

            selectedFile = file;
            
            // Hide current picture/icon
            if (currentPicture) currentPicture.style.display = 'none';
            if (profileIcon) profileIcon.style.display = 'none';
            
            // Show loading indicator
            loadingIndicator.style.display = 'flex';
            
            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-picture">`;
                previewContainer.style.display = 'block';
                loadingIndicator.style.display = 'none';
                profileActions.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        });
    }

    // Cancel Profile Update
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            resetProfilePictureDisplay();
            if (profileInput) profileInput.value = '';
            selectedFile = null;
        });
    }

    // Confirm Profile Update
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            if (!selectedFile) return;

            setLoadingState('profileForm', true);
            
            try {
                const formData = new FormData();
                formData.append('profile_picture', selectedFile);
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route('settings.profile.update') }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                setLoadingState('profileForm', false);

                if (result.success) {
                    showToast(result.message, 'success');
                    updateAllProfilePictures(result.newProfilePictureUrl);
                    resetProfilePictureDisplay(true, result.newProfilePictureUrl);
                } else {
                    showToast(result.message, 'error');
                    resetProfilePictureDisplay();
                }
            } catch (error) {
                setLoadingState('profileForm', false);
                showToast('An error occurred while updating your profile picture', 'error');
                resetProfilePictureDisplay();
            }
        });
    }

    // Reset Profile Picture Display
    function resetProfilePictureDisplay(keepChanges = false, newUrl = null) {
        loadingIndicator.style.display = 'none';
        profileActions.style.display = 'none';
        
        if (keepChanges && newUrl) {
            previewContainer.style.display = 'none';
            if (currentPicture) {
                currentPicture.src = newUrl;
                currentPicture.style.display = 'block';
            }
            if (profileIcon) profileIcon.style.display = 'none';
        } else {
            previewContainer.style.display = 'none';
            if (currentPicture) currentPicture.style.display = 'block';
            if (profileIcon) profileIcon.style.display = 'block';
        }
    }

    // Update all profile pictures on the page
    function updateAllProfilePictures(newUrl) {
        document.querySelectorAll('.profile-picture, .user-avatar').forEach(img => {
            img.src = newUrl;
        });
    }

    // Prevent default form submission for profile form
    document.getElementById('profileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    // Notification system
    function showNotification(message, duration = 3000) {
        const notification = document.getElementById('notificationCard');
        const messageEl = notification.querySelector('.notification-message');
        const closeBtn = notification.querySelector('.notification-close');

        // Set message
        messageEl.textContent = message;

        // Show notification
        notification.style.display = 'flex';
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto hide after duration
        const timeout = setTimeout(() => {
            hideNotification();
        }, duration);

        // Close button handler
        closeBtn.onclick = () => {
            clearTimeout(timeout);
            hideNotification();
        };
    }

    function hideNotification() {
        const notification = document.getElementById('notificationCard');
        notification.classList.remove('show');
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }

    // Update currency form handling
    document.addEventListener('DOMContentLoaded', function() {
        const currencyForm = document.getElementById('currencyForm');
        const currencySubmitBtn = document.getElementById('currencySubmitBtn');

        if (currencyForm && currencySubmitBtn) {
            currencyForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const selectedCurrency = document.querySelector('input[name="currency"]:checked');
                if (!selectedCurrency) {
                    showNotification('Please select a currency', 'error');
                    return;
                }

                try {
                    setLoadingState('currencyForm', true);
                    
                    const formData = new FormData(currencyForm);
                    const response = await fetch(currencyForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const data = await response.json();
                    setLoadingState('currencyForm', false);
                    
                    if (data.success) {
                        // Update localStorage
                        localStorage.setItem('userCurrency', data.currency);
                        localStorage.setItem('userCurrencySymbol', data.currencySymbol);
                        localStorage.setItem('userCurrencyName', data.currencyName);
                        
                        // Update all currency displays
                        document.querySelectorAll('.currency').forEach(el => {
                            el.textContent = data.currencySymbol;
                        });
                        
                        // Update the selected currency card
                        document.querySelectorAll('.currency-card').forEach(card => {
                            card.classList.remove('selected');
                            if (card.querySelector('input[type="radio"]').value === data.currency) {
                                card.classList.add('selected');
                            }
                        });
                        
                        // Show success notification
                        const notification = document.getElementById('notificationCard');
                        const messageEl = notification.querySelector('.notification-message');
                        const icon = notification.querySelector('.notification-icon');
                        
                        messageEl.textContent = data.message;
                        icon.className = 'fas fa-check-circle notification-icon';
                        notification.style.display = 'flex';
                        setTimeout(() => notification.classList.add('show'), 10);
                        
                        // Auto hide after 3 seconds
                        setTimeout(() => {
                            notification.classList.remove('show');
                            setTimeout(() => {
                                notification.style.display = 'none';
                            }, 300);
                        }, 3000);

                        // Trigger a custom event to notify other components
                        window.dispatchEvent(new CustomEvent('currencyUpdated', {
                            detail: {
                                currency: data.currency,
                                symbol: data.currencySymbol,
                                name: data.currencyName
                            }
                        }));

                        // Update any amount displays that need to be converted
                        document.querySelectorAll('[data-amount]').forEach(el => {
                            const amount = parseFloat(el.dataset.amount);
                            if (!isNaN(amount)) {
                                // Convert amount to new currency if needed
                                const oldCurrency = el.dataset.currency || 'PHP';
                                if (oldCurrency !== data.currency) {
                                    try {
                                        const convertedAmount = await convertAmount(amount, oldCurrency, data.currency);
                                        el.textContent = data.currencySymbol + convertedAmount.toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                        el.dataset.currency = data.currency;
                                    } catch (error) {
                                        console.error('Error converting amount:', error);
                                    }
                                }
                            }
                        });
                    } else {
                        // Show error notification
                        const notification = document.getElementById('notificationCard');
                        const messageEl = notification.querySelector('.notification-message');
                        const icon = notification.querySelector('.notification-icon');
                        
                        messageEl.textContent = data.message;
                        icon.className = 'fas fa-exclamation-circle notification-icon';
                        notification.style.display = 'flex';
                        setTimeout(() => notification.classList.add('show'), 10);
                        
                        // Auto hide after 3 seconds
                        setTimeout(() => {
                            notification.classList.remove('show');
                            setTimeout(() => {
                                notification.style.display = 'none';
                            }, 300);
                        }, 3000);
                    }
                } catch (error) {
                    setLoadingState('currencyForm', false);
                    // Show error notification
                    const notification = document.getElementById('notificationCard');
                    const messageEl = notification.querySelector('.notification-message');
                    const icon = notification.querySelector('.notification-icon');
                    
                    messageEl.textContent = 'An error occurred while updating your currency';
                    icon.className = 'fas fa-exclamation-circle notification-icon';
                    notification.style.display = 'flex';
                    setTimeout(() => notification.classList.add('show'), 10);
                    
                    // Auto hide after 3 seconds
                    setTimeout(() => {
                        notification.classList.remove('show');
                        setTimeout(() => {
                            notification.style.display = 'none';
                        }, 300);
                    }, 3000);
                    
                    console.error('Error:', error);
                }
            });
        }

        // Helper function to convert amounts between currencies
        async function convertAmount(amount, fromCurrency, toCurrency) {
            try {
                const response = await fetch(`/api/exchange-rate?from=${fromCurrency}&to=${toCurrency}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to get exchange rate');
                
                const data = await response.json();
                if (!data.success) throw new Error(data.message);
                
                return amount * data.rate;
            } catch (error) {
                console.error('Error converting amount:', error);
                return amount; // Return original amount if conversion fails
            }
        }
    });

    // Listen for currency updates
    window.addEventListener('currencyUpdated', function(e) {
        const { currency, symbol, name } = e.detail;
        
        // Update all currency displays
        document.querySelectorAll('.currency').forEach(el => {
            el.textContent = symbol;
        });
        
        // Update any amount displays that need to be converted
        document.querySelectorAll('[data-amount]').forEach(el => {
            const amount = parseFloat(el.dataset.amount);
            if (!isNaN(amount)) {
                el.textContent = symbol + amount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
    });

    // Add click handlers for currency cards
    document.querySelectorAll('.currency-card').forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            document.querySelectorAll('.currency-card').forEach(c => c.classList.remove('selected'));
            // Add selected class to clicked card
            this.classList.add('selected');
            // Check the radio input
            this.querySelector('input[type="radio"]').checked = true;
        });
    });

    // Show any server-side messages
    if (serverMessages.success) {
        showToast(serverMessages.success, 'success');
    }
    if (serverMessages.error) {
        showToast(serverMessages.error, 'error');
    }

    // Add this CSS to your existing styles
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            #password-requirements {
                list-style: none;
                padding: 0;
                margin: var(--space-sm) 0;
                font-size: 0.875rem;
            }

            #password-requirements li {
                display: flex;
                align-items: center;
                gap: var(--space-xs);
                margin-bottom: var(--space-xs);
                color: var(--text-muted);
            }

            #password-requirements li.met {
                color: var(--success);
            }

            #password-requirements li i {
                font-size: 0.75rem;
            }

            .strength-meter {
                height: 4px;
                background-color: var(--light-gray);
                border-radius: 2px;
                margin: var(--space-xs) 0;
                overflow: hidden;
            }

            .strength-bar {
                height: 100%;
                width: 0%;
                transition: width 0.3s ease, background-color 0.3s ease;
            }

            .strength-message {
                font-size: 0.8125rem;
                color: var(--text-muted);
                margin-top: var(--space-xs);
            }

            /* Add these styles to your existing CSS */
            .currency-preview {
                margin-top: 1.5rem;
                padding: 1rem;
                background-color: var(--light);
                border-radius: var(--radius-md);
                border: 1px solid var(--border-color);
            }

            .currency-preview h4 {
                margin: 0 0 1rem 0;
                color: var(--text-color);
                font-size: 1rem;
            }

            .preview-content {
                display: grid;
                gap: 0.75rem;
            }

            .preview-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                background-color: white;
                border-radius: var(--radius-sm);
            }

            .preview-label {
                color: var(--text-muted);
                font-size: 0.875rem;
            }

            .preview-value {
                font-weight: 500;
                color: var(--text-color);
            }

            .currency-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
                margin-top: 1rem;
            }

            .currency-card {
                position: relative;
                border: 2px solid var(--border-color);
                border-radius: 8px;
                padding: 1.25rem;
                cursor: pointer;
                background: white;
                transition: all 0.3s ease;
            }

            .currency-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                border-color: var(--primary);
            }

            .currency-card.selected {
                border-color: var(--primary);
                background-color: var(--primary-light);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .currency-card input[type="radio"] {
                position: absolute;
                opacity: 0;
            }

            .currency-content {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .currency-symbol {
                font-size: 1.75rem;
                font-weight: 600;
                color: var(--text-color);
                transition: color 0.3s ease;
            }

            .currency-name {
                font-size: 0.875rem;
                color: var(--text-muted);
                transition: color 0.3s ease;
            }

            .currency-code {
                font-size: 0.75rem;
                color: var(--text-muted);
                text-transform: uppercase;
                transition: color 0.3s ease;
            }

            .currency-card.selected .currency-symbol,
            .currency-card.selected .currency-name,
            .currency-card.selected .currency-code {
                color: var(--primary);
            }

            .currency-card:hover .currency-symbol,
            .currency-card:hover .currency-name,
            .currency-card:hover .currency-code {
                color: var(--primary);
            }

            @keyframes select-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }

            .currency-card.selected {
                animation: select-pulse 0.3s ease-in-out;
            }

            /* Add these styles to your existing CSS */
            .notification-card {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                z-index: 1000;
                min-width: 300px;
                max-width: 400px;
                transform: translateX(120%);
                transition: transform 0.3s ease;
            }

            .notification-card.show {
                transform: translateX(0);
            }

            .notification-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .notification-icon {
                color: var(--success);
                font-size: 1.25rem;
            }

            .notification-message {
                color: var(--text-color);
                font-size: 0.875rem;
                line-height: 1.4;
            }

            .notification-close {
                background: none;
                border: none;
                color: var(--text-muted);
                cursor: pointer;
                padding: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s ease;
            }

            .notification-close:hover {
                color: var(--text-color);
            }

            @keyframes slideIn {
                from { transform: translateX(120%); }
                to { transform: translateX(0); }
            }

            @keyframes slideOut {
                from { transform: translateX(0); }
                to { transform: translateX(120%); }
            }
        </style>
    `);
});
</script>
@endsection