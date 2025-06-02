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

// Enhanced Login Validation with Database Authentication
async function validateLogin() {
    console.log('🔐 Starting login validation...');
    
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const rememberMe = document.getElementById("rememberMe").checked;

    // Input validation
    if (!username) {
        showToast("⚠️ Please enter your username/email", 'warning');
        await showCustomAlert(
            "Missing Username", 
            "Please enter your username or email address to continue.", 
            'warning'
        );
        return;
    }

    if (!password) {
        showToast("⚠️ Please enter your password", 'warning');
        await showCustomAlert(
            "Missing Password", 
            "Please enter your password to continue.", 
            'warning'
        );
        return;
    }

    // Show loading state
    showLoading(true);
    showToast("🔄 Authenticating with database...", 'info', 2000);

    try {
        // Prepare login data
        const loginData = {
            action: 'login',
            username: username,
            password: password,
            rememberMe: rememberMe
        };

        console.log('📤 Sending login request to API...', { username, rememberMe });

        // Send request to your authentication API
        const response = await fetch('/gaming-zone/api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(loginData)
        });

        console.log('📡 Response status:', response.status);

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Invalid response format');
            const textResponse = await response.text();
            console.error('Response text:', textResponse);
            throw new Error('Server returned invalid response format. Please check server configuration.');
        }

        const result = await response.json();
        console.log('📥 Login response:', result);

        if (result.success && result.user) {
            console.log('✅ Database authentication successful!', result.user);
            
            // Store user data
            if (rememberMe && result.token) {
                setCookie('auth_token', result.token, 30);
                setCookie('user_id', result.user.id, 30);
                console.log('🍪 Remember me cookies set');
            }

            localStorage.setItem('gaming_zone_user', JSON.stringify(result.user));
            console.log('💾 User data stored');
            
            showToast("✅ Login successful! Welcome back!", 'success', 3000);
            await showCustomAlert(
                "Welcome Back!", 
                `Hello ${result.user.username || result.user.email}!\n\nLogin successful! You will be redirected to your dashboard.`, 
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
            console.error('❌ Database authentication failed:', result);
            
            // Enhanced error handling for database responses
            let errorTitle = "Login Failed";
            let errorMessage = "";

            if (result.error) {
                if (result.error.includes('User not found') || result.error.includes('not exist')) {
                    errorTitle = "Account Not Found";
                    errorMessage = `The ${username.includes('@') ? 'email' : 'username'} "${username}" is not registered in our system.\n\nPlease check your credentials or create a new account.`;
                    
                    // Show account creation option
                    showAccountNotFoundModal(username);
                    return;
                    
                } else if (result.error.includes('password') || result.error.includes('Invalid credentials')) {
                    errorTitle = "Incorrect Password";
                    errorMessage = "The password you entered is incorrect.\n\nPlease check your password and try again.\n\nTip: Password is case-sensitive.";
                    
                } else if (result.error.includes('account') && result.error.includes('locked')) {
                    errorTitle = "Account Locked";
                    errorMessage = "Your account has been temporarily locked for security reasons.\n\nPlease contact support or try again later.";
                    
                } else {
                    errorTitle = "Authentication Error";
                    errorMessage = result.error;
                }
            } else {
                errorMessage = "Unable to authenticate your credentials.\n\nPlease check your username/email and password.";
            }

            showToast("❌ Authentication failed!", 'error', 5000);
            await showCustomAlert(errorTitle, errorMessage, 'error');

            // Clear password and focus username
            document.getElementById("password").value = "";
            document.getElementById("username").focus();
            document.getElementById("username").select();
        }
        
    } catch (error) {
        console.error('❌ Login error:', error);
        
        let errorTitle = "Connection Error";
        let errorMessage = "";
        
        if (error.message.includes('fetch') || error.message.includes('network')) {
            errorTitle = "Network Error";
            errorMessage = "Unable to connect to the server.\n\nPlease check your internet connection and try again.";
        } else if (error.message.includes('Server')) {
            errorTitle = "Server Error";
            errorMessage = "The server is currently experiencing issues.\n\nPlease try again later or contact support.";
        } else {
            errorMessage = error.message;
        }
        
        showToast("❌ Connection failed!", 'error');
        await showCustomAlert(errorTitle, errorMessage, 'error');
        
    } finally {
        showLoading(false);
    }
}

// Show account not found modal with options
function showAccountNotFoundModal(username) {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10002;
            backdrop-filter: blur(10px);
        ">
            <div style="
                background: linear-gradient(145deg, rgba(15, 25, 45, 0.95), rgba(25, 35, 65, 0.95));
                border: 2px solid #ff6b6b;
                border-radius: 20px;
                padding: 40px;
                max-width: 500px;
                width: 90%;
                text-align: center;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
                color: white;
                font-family: 'Rajdhani', sans-serif;
            ">
                <div style="font-size: 4rem; margin-bottom: 20px;">🚫</div>
                <h2 style="
                    color: #ff6b6b;
                    margin-bottom: 20px;
                    font-family: 'Orbitron', monospace;
                    font-size: 1.8rem;
                ">Account Not Found!</h2>
                <p style="
                    font-size: 1.1rem;
                    margin-bottom: 30px;
                    line-height: 1.6;
                    color: rgba(255, 255, 255, 0.9);
                ">
                    The ${username.includes('@') ? 'email' : 'username'} <strong style="color: #00ffff;">${username}</strong> is not registered in our system.
                </p>
                <div style="
                    background: rgba(0, 255, 255, 0.1);
                    border: 1px solid rgba(0, 255, 255, 0.3);
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 30px;
                ">
                    <p style="margin: 0; font-size: 1rem;">
                        💡 <strong>What would you like to do?</strong>
                    </p>
                </div>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button onclick="redirectToCreateAccount()" style="
                        background: linear-gradient(135deg, #00ff80, #00ffff);
                        color: #000;
                        border: none;
                        padding: 15px 25px;
                        border-radius: 10px;
                        font-weight: 700;
                        cursor: pointer;
                        font-family: 'Orbitron', monospace;
                        transition: all 0.3s ease;
                    ">
                        ➕ Create Account
                    </button>
                    <button onclick="tryDifferentCredentials()" style="
                        background: linear-gradient(135deg, #ffaa00, #ff6600);
                        color: #000;
                        border: none;
                        padding: 15px 25px;
                        border-radius: 10px;
                        font-weight: 700;
                        cursor: pointer;
                        font-family: 'Orbitron', monospace;
                        transition: all 0.3s ease;
                    ">
                        🔄 Try Different Credentials
                    </button>
                    <button onclick="this.closest('div').remove()" style="
                        background: rgba(255, 255, 255, 0.1);
                        color: white;
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        padding: 15px 25px;
                        border-radius: 10px;
                        font-weight: 700;
                        cursor: pointer;
                        font-family: 'Orbitron', monospace;
                        transition: all 0.3s ease;
                    ">
                        ❌ Cancel
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Modal action functions
window.redirectToCreateAccount = function() {
    showToast("🚀 Redirecting to account creation...", 'info', 2000);
    setTimeout(() => {
        window.location.href = '/gaming-zone/pages/createaccount/createaccount.html';
    }, 1000);
};

window.tryDifferentCredentials = function() {
    document.querySelector('[style*="position: fixed"]').remove();
    document.getElementById('username').focus();
    document.getElementById('username').select();
    showToast("💡 Try entering different login credentials", 'info', 3000);
};

// Demo login function with database fallback
async function demoLogin() {
    console.log('🎮 Starting demo login...');
    
    showToast("🎮 Setting up demo login...", 'info', 2000);
    
    try {
        // Fill demo credentials
        document.getElementById("username").value = "demo@gaming.com";  // Use a demo email
        document.getElementById("password").value = "demo123";  // Use demo password
        document.getElementById("rememberMe").checked = true;
        
        // Small delay to show the form fill
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        showToast("✅ Demo credentials loaded! Authenticating...", 'success', 2000);
        
        // Try database authentication first
        await validateLogin();
        
    } catch (error) {
        console.error('❌ Demo login failed:', error);
        showToast("❌ Demo login failed. Please try manual login.", 'error');
        await showCustomAlert(
            "Demo Login Error",
            "The demo login feature encountered an error.\n\nPlease try logging in manually or contact support.",
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

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Enhanced login script loaded');
    
    // Check for registration success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registered') === 'true') {
        document.getElementById('registrationSuccess').style.display = 'block';
        showToast("🎉 Account created successfully! Please sign in.", 'success', 5000);
    }
    
    // Form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        validateLogin();
    });
    
    // Demo login button
    document.getElementById('demoLoginBtn').addEventListener('click', demoLogin);
    
    // Password toggle
    document.getElementById('togglePassword').addEventListener('click', togglePasswordVisibility);
    
    // Forgot password
    document.getElementById('forgotPasswordLink').addEventListener('click', function(e) {
        e.preventDefault();
        forgotPassword();
    });
    
    // Add click handlers for new account buttons
    const newAccountBtns = document.querySelectorAll('.new-account-btn, .create-account-btn');
    newAccountBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            redirectToCreateAccount();
        });
    });
    
    // Enter key support
    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            validateLogin();
        }
    });
    
    document.getElementById('username').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            validateLogin();
        }
    });
    
    console.log('🔐 Database authentication system ready');
});
