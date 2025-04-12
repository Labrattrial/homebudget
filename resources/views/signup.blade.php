<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up</title>

  <!-- Bootstrap CSS for Modal -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  @vite(['resources/css/signup.css'])
</head>
<body>
  <div class="signup-wrapper">
    <div class="signup-card">
      <div class="avatar">
        <img src="https://cdn-icons-png.flaticon.com/512/147/147144.png" alt="User Avatar"/>
      </div>

      <h2>Create Account</h2>

      <!-- Success Message -->
      @if (session('success'))
        <div class="alert alert-success">
          {{ session('success') }}
        </div>
      @endif

      <!-- Validation Errors -->
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="signupForm" method="POST" action="{{ url('/signup') }}">
        @csrf

        <!-- Name -->
        <input
          type="text"
          name="name"
          placeholder="Name"
          value="{{ old('name') }}"
          required
          pattern="[A-Za-z\s]+"
          title="Name should only contain letters and spaces."
        />

        <!-- Email -->
        <input
          type="email"
          name="email"
          placeholder="Email"
          value="{{ old('email') }}"
          required
          pattern="[^@]+@[^@]+\.[a-z]{2,}"
          title="Enter a valid email address with full domain (e.g. user@example.com)"
        />

        <!-- Password -->
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
          title="Password must be at least 8 characters, include an uppercase letter, a lowercase letter, and a number."
        />

        <!-- Confirm Password -->
        <input
          type="password"
          name="password_confirmation"
          placeholder="Confirm Password"
          required
        />

        <!-- Terms and Conditions -->
        <label class="terms-check">
          <input type="checkbox" required />
          I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
        </label>

        <!-- Submit Button -->
        <button type="submit" class="register-btn">Register</button>
      </form>
    </div>
  </div>

  <!-- Terms Modal -->
  <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Terms and Conditions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>PWelcome to Home Budget. By accessing or using our website and services, you agree to comply with and be bound by the following Terms and Conditions. If you do not agree with these Terms, please do not use our service. Home Budget is intended for personal finance management only. You must be at least 13 years old or have parental or guardian consent to use the application. You are responsible for maintaining the confidentiality of your account and any activity under your login credentials.

By using our services, you consent to the collection, storage, and processing of your personal data in accordance with our Privacy Policy. We are committed to protecting your privacy and will not share or sell your data to third parties unless required by law. All content, features, and designs within the Home Budget platform are the property of the developer and are protected by copyright and intellectual property laws. You may not reproduce, distribute, or modify any part of the application without written permission.
Home Budget is provided on an "as-is" and "as-available" basis. We make no warranties, express or implied, regarding the functionality or availability of the service and shall not be held liable for any financial losses, data loss, or damages resulting from your use or inability to use the application. We reserve the right to modify these Terms at any time. Any continued use of the application after updates constitutes your acceptance of the revised Terms.

We also reserve the right to terminate or suspend your access to the application at any time, without prior notice, if we believe you have violated any part of these Terms. For questions or concerns regarding these Terms and Conditions, please contact us at [your-email@example.com].</p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
