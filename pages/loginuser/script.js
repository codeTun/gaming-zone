console.log('🚀 Enhanced Login System Loaded');

// Toast Notification System
function showToast(message, type = 'error', duration = 5000) {
    const container = document.getElementById('toast-container');
    
    // Remove existing toasts of same type
    const existingToasts = container.querySelectorAll(`.toast.${type}`);
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        error: '❌',
        success: '✅',
        warning: '⚠️',
        info: 'ℹ️'
    };

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 1.5rem;">${icons[type]}</span>
            <span style="flex: 1; font-size: 1.1rem;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                transition: all 0.3s ease;
            ">✕</button>
        </div>
    `;

    container.appendChild(toast);

    // Auto remove
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutToRight 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }
    }, duration);

    // Click to dismiss
    toast.addEventListener('click', () => {
        toast.style.animation = 'slideOutToRight 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    });
}

// Custom Alert System
function showCustomAlert(title, message, type = 'error') {
    return new Promise((resolve) => {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.custom-alert-overlay');
        existingAlerts.forEach(alert => alert.remove());

        const overlay = document.createElement('div');
        overlay.className = 'custom-alert-overlay';
        
        const alertStyles = {
            error: {
                gradient: 'linear-gradient(135deg, #ff4757, #ff3742)',
                borderColor: '#ff6b7a',
                icon: '🚫',
                iconColor: '#ff4757'
            },
            success: {
                gradient: 'linear-gradient(135deg, #2ed573, #7bed9f)',
                borderColor: '#2ed573',
                icon: '🎉',
                iconColor: '#2ed573'
            },
            warning: {
                gradient: 'linear-gradient(135deg, #ffa502, #ff9f43)',
                borderColor: '#ffa502',
                icon: '⚠️',
                iconColor: '#ffa502'
            }
        };

        const style = alertStyles[type] || alertStyles.error;

        overlay.innerHTML = `
            <div class="custom-alert-box" style="
                background: linear-gradient(145deg, rgba(15, 25, 45, 0.98), rgba(25, 35, 65, 0.98));
                border: 3px solid ${style.borderColor};
                border-radius: 25px;
                padding: 40px;
                max-width: 450px;
                width: 90%;
                text-align: center;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 40px ${style.borderColor}40;
                backdrop-filter: blur(25px);
                color: white;
                font-family: 'Rajdhani', sans-serif;
                animation: alertSlideIn 0.6s ease;
            ">
                <div style="font-size: 4rem; margin-bottom: 20px;">${style.icon}</div>
                <h2 style="
                    font-family: 'Orbitron', monospace;
                    font-size: 1.8rem;
                    margin-bottom: 20px;
                    color: ${style.iconColor};
                    text-shadow: 0 0 20px ${style.iconColor}80;
                ">${title}</h2>
                <p style="
                    font-size: 1.2rem;
                    line-height: 1.6;
                    margin-bottom: 30px;
                    color: rgba(255, 255, 255, 0.9);
                    white-space: pre-line;
                ">${message}</p>
                <button onclick="closeCustomAlert()" style="
                    background: ${style.gradient};
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 15px;
                    font-size: 1.1rem;
                    font-weight: 700;
                    font-family: 'Orbitron', monospace;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    box-shadow: 0 10px 30px ${style.iconColor}40;
                ">
                    OK
                </button>
            </div>
        `;

        // Overlay styles
        Object.assign(overlay.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            background: 'rgba(0, 0, 0, 0.9)',
            backdropFilter: 'blur(10px)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: '10001'
        });

        // Add animations CSS
        if (!document.getElementById('alert-animations')) {
            const style = document.createElement('style');
            style.id = 'alert-animations';
            style.textContent = `
                @keyframes alertSlideIn {
                    0% {
                        transform: scale(0.7) translateY(-50px);
                        opacity: 0;
                    }
                    60% {
                        transform: scale(1.05) translateY(0);
                        opacity: 1;
                    }
                    100% {
                        transform: scale(1) translateY(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutToRight {
                    0% {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    100% {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Global close function
        window.closeCustomAlert = () => {
            const alertBox = overlay.querySelector('.custom-alert-box');
            alertBox.style.animation = 'alertSlideOut 0.4s ease';
            setTimeout(() => {
                if (overlay.parentElement) {
                    overlay.remove();
                }
                resolve();
            }, 400);
        };

        document.body.appendChild(overlay);
    });
}

// Enhanced Login Validation - FIXED FOR CORRECT API PATH
async function validateLogin() {
    console.log('🔐 Starting login validation...');
    
    // Get form elements
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const rememberMeInput = document.getElementById("rememberMe");

    // Debug logging
    console.log('🔍 Form elements found:', {
        emailInput: !!emailInput,
        passwordInput: !!passwordInput,
        rememberMeInput: !!rememberMeInput
    });

    // Check if elements exist
    if (!emailInput) {
        console.error('❌ Email input element not found');
        showToast("❌ Form error: Email field not found", 'error');
        return;
    }

    if (!passwordInput) {
        console.error('❌ Password input element not found');
        showToast("❌ Form error: Password field not found", 'error');
        return;
    }

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();
    const rememberMe = rememberMeInput ? rememberMeInput.checked : false;

    console.log('📋 Login attempt:', {
        email: email ? 'provided' : 'empty',
        password: password ? 'provided' : 'empty',
        rememberMe: rememberMe
    });

    // Input validation
    if (!email) {
        showToast("⚠️ Please enter your email address", 'warning');
        await showCustomAlert(
            "Missing Email", 
            "Please enter your email address to continue.", 
            'warning'
        );
        emailInput.focus();
        return;
    }

    if (!password) {
        showToast("⚠️ Please enter your password", 'warning');
        await showCustomAlert(
            "Missing Password", 
            "Please enter your password to continue.", 
            'warning'
        );
        passwordInput.focus();
        return;
    }

    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToast("⚠️ Please enter a valid email address", 'warning');
        await showCustomAlert(
            "Invalid Email Format", 
            "Please enter a valid email address (e.g., user@example.com).", 
            'warning'
        );
        emailInput.focus();
        emailInput.select();
        return;
    }

    // Show loading state
    showLoading(true);
    showToast("🔄 Authenticating with database...", 'info', 2000);

    try {
        // Prepare login data
        const loginData = {
            email: email,
            password: password
        };

        console.log('📤 Sending login request to correct API endpoint...');

        // Send request to your CORRECT API path that works in Postman
        const response = await fetch('/gaming-zone/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });

        console.log('📡 Response status:', response.status);
        console.log('📡 Response URL:', response.url);

        // Check response status first
        if (!response.ok) {
            console.error('❌ HTTP Error:', response.status, response.statusText);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Invalid response format');
            const textResponse = await response.text();
            console.error('Response text:', textResponse);
            
            // Show more specific error based on response
            if (textResponse.includes('Fatal error') || textResponse.includes('Parse error')) {
                throw new Error('Server configuration error. Please check PHP files and database connection.');
            } else if (textResponse.includes('404') || textResponse.includes('Not Found')) {
                throw new Error('Login API not found. Please check the API path.');
            } else {
                throw new Error('Server returned invalid response format.');
            }
        }

        const result = await response.json();
        console.log('📥 Login response:', result);

        if (result.success && result.user) {
            console.log('✅ Authentication successful!', result.user);
            
            // Store user data securely
            if (result.token) {
                if (rememberMe) {
                    localStorage.setItem('auth_token', result.token);
                    localStorage.setItem('user_id', result.user.id);
                    localStorage.setItem('token_type', result.token_type || 'Bearer');
                    console.log('💾 Persistent login data stored');
                } else {
                    sessionStorage.setItem('auth_token', result.token);
                    sessionStorage.setItem('user_id', result.user.id);
                    sessionStorage.setItem('token_type', result.token_type || 'Bearer');
                    console.log('🔒 Session-only login data stored');
                }
            }

            // Store user session data
            localStorage.setItem('gaming_zone_user', JSON.stringify(result.user));
            sessionStorage.setItem('user_authenticated', 'true');
            console.log('👤 User data stored:', result.user);
            
            showToast("✅ Login successful! Welcome back!", 'success', 3000);
            await showCustomAlert(
                "Welcome Back!", 
                `Hello ${result.user.username || result.user.name}!\n\nLogin successful! You will be redirected to your dashboard.`, 
                'success'
            );
            
            // Role-based redirection
            setTimeout(() => {
                console.log('🔄 Redirecting based on user role:', result.user.role);
                
                if (result.user.role === 'ADMIN' || result.user.role === 'admin') {
                    console.log('👑 Admin user detected, redirecting to admin dashboard...');
                    window.location.href = "/gaming-zone/admin-dashboard/index.html";
                } else {
                    console.log('👤 Regular user detected, redirecting to user interface...');
                    window.location.href = "/gaming-zone/user-interface/main.html";
                }
            }, 1500);
            
        } else {
            console.error('❌ Authentication failed:', result);
            
            // Enhanced error handling for UserManager API response structure
            let errorTitle = "Login Failed";
            let errorMessage = "";

            if (result.message) {
                const message = result.message.toLowerCase();
                
                if (message.includes('user not found') || message.includes('no user found')) {
                    errorTitle = "Account Not Found";
                    errorMessage = `The email "${email}" is not registered in our system.\n\nPlease check your email address or create a new account.`;
                    showAccountNotFoundModal(email);
                    return;
                    
                } else if (message.includes('password') || message.includes('invalid password')) {
                    errorTitle = "Incorrect Password";
                    errorMessage = "The password you entered is incorrect.\n\nPlease check your password and try again.\n\nTip: Password is case-sensitive.";
                    
                } else if (message.includes('email') && message.includes('format')) {
                    errorTitle = "Invalid Email";
                    errorMessage = "Please enter a valid email address format.";
                    
                } else if (message.includes('required')) {
                    errorTitle = "Missing Information";
                    errorMessage = result.message;
                    
                } else if (message.includes('server error') || message.includes('database')) {
                    errorTitle = "Server Error";
                    errorMessage = "The server is experiencing issues.\n\nPlease try again later or contact support.";
                    
                } else {
                    errorTitle = "Authentication Error";
                    errorMessage = result.message;
                }
            } else {
                errorMessage = "Unable to authenticate your credentials.\n\nPlease check your email and password.";
            }

            showToast("❌ Authentication failed!", 'error', 5000);
            await showCustomAlert(errorTitle, errorMessage, 'error');

            // Clear password and focus email
            passwordInput.value = "";
            emailInput.focus();
            emailInput.select();
        }
        
    } catch (error) {
        console.error('❌ Login error:', error);
        
        let errorTitle = "Connection Error";
        let errorMessage = "";
        
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            errorTitle = "Network Error";
            errorMessage = "Unable to connect to the server.\n\nPlease check your internet connection and try again.";
        } else if (error.message.includes('HTTP 404')) {
            errorTitle = "API Not Found";
            errorMessage = "The login API is not available at the expected location.\n\nPlease check the server configuration.";
        } else if (error.message.includes('HTTP 500')) {
            errorTitle = "Server Error";
            errorMessage = "The server encountered an internal error.\n\nPlease try again later or contact support.";
        } else if (error.message.includes('configuration') || error.message.includes('PHP')) {
            errorTitle = "Server Configuration Error";
            errorMessage = "The server is not properly configured.\n\nPlease contact support.";
        } else {
            errorTitle = "Login Error";
            errorMessage = error.message || "An unexpected error occurred during login.";
        }
        
        showToast("❌ Login failed!", 'error');
        await showCustomAlert(errorTitle, errorMessage, 'error');
        
    } finally {
        showLoading(false);
    }
}

// Demo login function - UPDATED FOR CORRECT API
async function demoLogin() {
    console.log('🎮 Starting demo login...');
    
    // Check if form elements exist
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const rememberMeInput = document.getElementById("rememberMe");

    if (!emailInput || !passwordInput) {
        console.error('❌ Demo login failed: Required form elements not found');
        showToast("❌ Demo login failed: Form elements not found", 'error');
        return;
    }
    
    showToast("🎮 Setting up demo login...", 'info', 2000);
    
    try {
        // Fill with demo account (make sure this exists in your database)
        emailInput.value = "demo@gaming.com";
        passwordInput.value = "demo123";
        if (rememberMeInput) {
            rememberMeInput.checked = true;
        }
        
        console.log('✅ Demo credentials filled successfully');
        
        // Small delay to show the form fill
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        showToast("✅ Demo credentials loaded! Authenticating...", 'success', 2000);
        
        // Try database authentication
        await validateLogin();
        
    } catch (error) {
        console.error('❌ Demo login failed:', error);
        showToast("❌ Demo login failed. Please try manual login.", 'error');
        await showCustomAlert(
            "Demo Login Error",
            "The demo login feature encountered an error.\n\nPlease create a demo account first or try logging in manually with:\n\nEmail: demo@gaming.com\nPassword: demo123",
            'error'
        );
    }
}

// Show/Hide loading overlay
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = show ? 'flex' : 'none';
    
    // Update button state
    const loginBtn = document.getElementById('loginBtn');
    const demoBtn = document.getElementById('demoLoginBtn');
    
    if (show) {
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Authenticating...</span>';
        demoBtn.disabled = true;
    } else {
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Sign In</span>';
        demoBtn.disabled = false;
    }
}

// Toggle password visibility
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'fas fa-eye';
    }
}

// Forgot password function
async function forgotPassword() {
    await showCustomAlert(
        "Forgot Password",
        "Forgot Password feature coming soon!\n\nFor now, please contact support.\n\nSupport: support@gaming-zone.com",
        'info'
    );
}

// Cookie utility functions
function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
    console.log(`🍪 Cookie set: ${name}`);
}

// Add redirect functionality for new account buttons
function redirectToCreateAccount() {
    console.log('🔄 Redirecting to create account page...');
    showToast("🚀 Redirecting to account creation...", 'info', 2000);
    setTimeout(() => {
        window.location.href = '/gaming-zone/pages/createaccount/createaccount.html';
    }, 1000);
}

// Initialize when page loads - FIXED ELEMENT SELECTORS
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Enhanced login script loaded');
    
    // Enhanced debugging
    console.log('🔍 DOM Debug Info:');
    console.log('- Email input:', document.getElementById('email'));
    console.log('- Password input:', document.getElementById('password'));
    console.log('- Login form:', document.getElementById('loginForm'));
    console.log('- Demo button:', document.getElementById('demoLoginBtn'));
    
    // Check for registration success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registered') === 'true') {
        const successElement = document.getElementById('registrationSuccess');
        if (successElement) {
            successElement.style.display = 'block';
        }
        showToast("🎉 Account created successfully! Please sign in.", 'success', 5000);
    }
    
    // Form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        console.log('✅ Login form found and event listener added');
        loginForm.addEventListener('submit', function(e) {
            console.log('📝 Form submit event triggered');
            e.preventDefault();
            validateLogin();
        });
    } else {
        console.error('❌ Login form not found');
    }
    
    // Demo login button
    const demoBtn = document.getElementById('demoLoginBtn');
    if (demoBtn) {
        console.log('✅ Demo button found and event listener added');
        demoBtn.addEventListener('click', function(e) {
            console.log('🎮 Demo button clicked');
            e.preventDefault();
            demoLogin();
        });
    } else {
        console.warn('⚠️ Demo button not found');
    }
    
    // Password toggle
    const toggleBtn = document.getElementById('togglePassword');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', togglePasswordVisibility);
    }
    
    // Forgot password
    const forgotLink = document.getElementById('forgotPasswordLink');
    if (forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            forgotPassword();
        });
    }
    
    // Add click handlers for new account buttons
    const newAccountBtns = document.querySelectorAll('.new-account-btn, .create-account-btn');
    newAccountBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            redirectToCreateAccount();
        });
    });
    
    // Enter key support
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validateLogin();
            }
        });
    }
    
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validateLogin();
            }
        });
    }
    
    console.log('🔐 Database authentication system ready');
    console.log('🔍 Final element check:', {
        loginForm: !!document.getElementById('loginForm'),
        emailInput: !!document.getElementById('email'),
        passwordInput: !!document.getElementById('password'),
        rememberMe: !!document.getElementById('rememberMe'),
        demoBtn: !!document.getElementById('demoLoginBtn')
    });
});
