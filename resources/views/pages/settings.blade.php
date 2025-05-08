@extends('layouts.main')

@section('content')
@vite(['resources/css/settings.css', 'resources/js/fontawesome.js'])

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container"></div>

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

<div class="profile-settings-container">
    <div class="profile-settings-card">
        <h2 class="profile-header">Profile Settings</h2>

        {{-- Profile Picture Form --}}
<form id="profileForm" enctype="multipart/form-data">
    @csrf
    <div class="profile-picture-container">
        <label for="profile_picture" class="profile-picture-label">
            <div class="profile-picture-wrapper">
                @if(Auth::user()->profile_picture)
                    <img src="{{ Auth::user()->profile_picture }}" 
                        alt="Profile Picture" 
                        class="profile-picture"
                        id="currentProfilePicture">
                @else
                    <i class="fas fa-user-circle profile-picture-icon" id="profileIcon"></i>
                @endif
                <div class="profile-picture-preview" id="profilePicturePreview" style="display: none;"></div>
                <div class="profile-picture-loading" id="profilePictureLoading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
            <p class="profile-picture-text">Tap to change profile picture</p>
        </label>
        <input type="file" name="profile_picture" id="profile_picture" style="display: none;" accept="image/*">
        
        <!-- Move the action buttons inside the container -->
        <div class="profile-picture-actions" id="profilePictureActions" style="display: none;">
            <button type="button" class="btn btn-light" id="cancelProfileUpdate">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="confirmProfileUpdate">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
    
    <!-- Keep the submit button but hide it initially -->
    <div class="form-actions" id="profileFormSubmit" style="display: none;">
        <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
            <span class="btn-text">Update Profile Picture</span>
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
            <h3 class="section-header">Change Password</h3>

            <div class="form-group">
                <input type="password" 
                       name="current_password" 
                       placeholder="Current Password"
                       class="form-control"
                       id="current_password"
                       required>
                <div class="validation-message" id="current-password-error"></div>
            </div>

            <div class="form-group">
                <div class="password-field-container">
                    <input type="password" 
                           name="new_password" 
                           placeholder="New Password"
                           class="form-control"
                           id="new_password" 
                           required>
                    <button type="button" class="password-toggle" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength-meter" class="strength-meter">
                    <div class="strength-bar" data-strength="0"></div>
                    <span class="strength-message">Password strength</span>
                </div>
                <div class="validation-message" id="new-password-error"></div>
            </div>

            <div class="form-group">
                <div class="password-field-container">
                    <input type="password" 
                           name="new_password_confirmation"
                           placeholder="Confirm New Password"
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
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return Math.min(strength, 5);
    }

    function updateStrengthMeter(password) {
        const strength = checkPasswordStrength(password);
        const meter = document.querySelector('.strength-bar');
        const message = document.querySelector('.strength-message');
        
        meter.style.width = `${strength * 20}%`;
        meter.dataset.strength = strength;
        
        const messages = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong', 'Excellent'];
        message.textContent = messages[strength];
        
        const colors = ['#ff4d4d', '#ff9966', '#ffcc00', '#99cc33', '#66cc33', '#339900'];
        meter.style.backgroundColor = colors[strength];
    }

    // Client-side Validation
    function validatePasswordForm() {
        let isValid = true;
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

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
        } else if (newPassword.length < 8) {
            document.getElementById('new-password-error').textContent = 'Password must be at least 8 characters';
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
    const profileFormSubmit = document.getElementById('profileFormSubmit');
    
    let selectedFile = null;

    // Profile Picture Change Handler
    if (profileInput) {
        profileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            selectedFile = file;
            loadingIndicator.style.display = 'block';
            
            if (currentPicture) currentPicture.style.display = 'none';
            if (profileIcon) profileIcon.style.display = 'none';
            previewContainer.style.display = 'none';
            if (profileActions) profileActions.style.display = 'none';
            if (profileFormSubmit) profileFormSubmit.style.display = 'none';

            if (!file.type.match('image.*')) {
                showToast('Please select a valid image file', 'error');
                resetProfilePictureDisplay();
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-picture">`;
                previewContainer.style.display = 'block';
                loadingIndicator.style.display = 'none';
                if (profileActions) profileActions.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        });
    }

    // Cancel Profile Update
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            resetProfilePictureDisplay();
            if (profileInput) profileInput.value = '';
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
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (profileActions) profileActions.style.display = 'none';
        if (profileFormSubmit) profileFormSubmit.style.display = 'none';
        
        if (keepChanges && newUrl) {
            if (previewContainer) previewContainer.style.display = 'none';
            if (currentPicture) {
                currentPicture.src = newUrl;
                currentPicture.style.display = 'block';
            }
            if (profileIcon) profileIcon.style.display = 'none';
        } else {
            if (previewContainer) previewContainer.style.display = 'none';
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

    // Show any server-side messages
    if (serverMessages.success) {
        showToast(serverMessages.success, 'success');
    }
    if (serverMessages.error) {
        showToast(serverMessages.error, 'error');
    }
});
</script>
@endsection