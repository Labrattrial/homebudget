<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - HomeBudget</title>
  @vite(['resources/css/login.css'])
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Loader -->
  <div id="loader">
      <div class="spinner"></div>
    </div>

  <div class="container">
    <div class="left-panel">
      <h1>Welcome</h1>
      <p class="subtitle">Log in to manage your finances efficiently</p>

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form class="login-form" method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="input-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            value="{{ old('email') }}"
            required
            autofocus
          >
          @error('email')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            >
            <span class="toggle-password" onclick="togglePassword()">
              <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path id="eye-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </span>
          </div>
          @error('password')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <!-- Login Button -->
      <button type="submit" class="sign-in-btn" id="loginBtn">
        Sign In
      </button>
      </form>
    </div>

    <div class="right-panel">
      <h2>New Here?</h2>
      <p class="info-text">Create an account and take control of your finances effortlessly.</p>
      <!-- Create Account Button -->
      <button type="button" class="sign-up-btn" id="createAccountBtn" onclick="window.location.href='{{ route('signup') }}'">
        Create Account
      </button>
    </div>
  </div>

  <script>
    let visible = false;
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const eyeIcon = document.getElementById("eye-icon");

      if (!visible) {
        passwordInput.type = "text";
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.06 10.06 0 013.224-4.621M6.53 6.53A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.266 5.167M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18" />
        `;
      } else {
        passwordInput.type = "password";
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
      visible = !visible;
    }
  </script>

  <script>
    // Hide loader when the page fully loads
    window.addEventListener('load', () => {
      const loader = document.getElementById('loader');
      loader.style.opacity = '0';
      setTimeout(() => loader.style.display = 'none', 300); // Fade out
    });
  </script>

  <script>
    // Show loader on Login button click
    const loginForm = document.querySelector("form.login-form");
    const loginBtn = document.getElementById("loginBtn");

    loginForm.addEventListener("submit", function (e) {
      loginBtn.disabled = true;
      loginBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Logging In...
      `;
    });
  </script>

  <script>
    // Show loader on Create Account button click
    const createAccountBtn = document.getElementById("createAccountBtn");

    createAccountBtn.addEventListener("click", function () {
      createAccountBtn.disabled = true;
      createAccountBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Redirecting...
      `;
    });
  </script>



</body>
</html>
