function showToast(message, type = 'error', duration = 5000) {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.gaming-toast');
    existingToasts.forEach(toast => toast.remove());

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `gaming-toast gaming-toast-${type}`;
    
    // Toast styles based on type
    const toastStyles = {
        error: {
            background: 'linear-gradient(135deg, #ff4757, #ff3742)',
            borderColor: '#ff6b7a',
            icon: '❌'
        },
        success: {
            background: 'linear-gradient(135deg, #2ed573, #7bed9f)',
            borderColor: '#5f27cd',
            icon: '✅'
        },
        warning: {
            background: 'linear-gradient(135deg, #ffa502, #ff9f43)',
            borderColor: '#ff7675',
            icon: '⚠️'
        },
        info: {
            background: 'linear-gradient(135deg, #70a1ff, #5352ed)',
            borderColor: '#3742fa',
            icon: 'ℹ️'
        }
    };

    const style = toastStyles[type] || toastStyles.error;

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 1.5rem;">${style.icon}</span>
            <span style="flex: 1; font-weight: 600; font-size: 1.1rem;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                transition: all 0.3s ease;
            " onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">✕</button>
        </div>
    `;

    // Apply styles
    Object.assign(toast.style, {
        position: 'fixed',
        top: '30px',
        right: '30px',
        background: style.background,
        color: 'white',
        padding: '20px 25px',
        borderRadius: '15px',
        border: `2px solid ${style.borderColor}`,
        boxShadow: '0 15px 35px rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 255, 255, 0.1)',
        backdropFilter: 'blur(15px)',
        WebkitBackdropFilter: 'blur(15px)',
        fontFamily: "'Rajdhani', sans-serif",
        fontSize: '1rem',
        fontWeight: '500',
        maxWidth: '400px',
        minWidth: '300px',
        zIndex: '10000',
        animation: 'slideInFromRight 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
        cursor: 'pointer'
    });

    // Add CSS animations
    if (!document.getElementById('toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideInFromRight {
                0% {
                    transform: translateX(100%);
                    opacity: 0;
                }
                100% {
                    transform: translateX(0);
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

    // Add toast to page
    document.body.appendChild(toast);

    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutToRight 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 500);
        }
    }, duration);

    // Click to dismiss
    toast.addEventListener('click', () => {
        toast.style.animation = 'slideOutToRight 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 500);
    });
}

// Enhanced Alert System
function showCustomAlert(title, message, type = 'error') {
    return new Promise((resolve) => {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.gaming-alert-overlay');
        existingAlerts.forEach(alert => alert.remove());

        // Create alert overlay
        const overlay = document.createElement('div');
        overlay.className = 'gaming-alert-overlay';
        
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
            <div class="gaming-alert-box" style="
                background: linear-gradient(145deg, rgba(15, 25, 45, 0.98), rgba(25, 35, 65, 0.98));
                border: 3px solid ${style.borderColor};
                border-radius: 25px;
                padding: 40px;
                max-width: 450px;
                width: 90%;
                text-align: center;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 40px ${style.borderColor}40;
                backdrop-filter: blur(25px);
                -webkit-backdrop-filter: blur(25px);
                color: white;
                font-family: 'Rajdhani', sans-serif;
                animation: alertSlideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
                " onmouseover="this.style.transform='translateY(-3px) scale(1.05)'" 
                   onmouseout="this.style.transform='translateY(0) scale(1)'">
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
            WebkitBackdropFilter: 'blur(10px)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: '10001'
        });

        // Add CSS animations if not exists
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
                @keyframes alertSlideOut {
                    0% {
                        transform: scale(1) translateY(0);
                        opacity: 1;
                    }
                    100% {
                        transform: scale(0.7) translateY(-50px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Global close function
        window.closeCustomAlert = () => {
            const alertBox = overlay.querySelector('.gaming-alert-box');
            alertBox.style.animation = 'alertSlideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => {
                if (overlay.parentElement) {
                    overlay.remove();
                }
                resolve();
            }, 400);
        };

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                window.closeCustomAlert();
            }
        });

        document.body.appendChild(overlay);
    });
}

// Enhanced Login Validation
async function validateLogin() {
    console.log('🔐 Starting login validation...');
    
    const username = document.getElementById("username")?.value || 
                    document.getElementById("email")?.value || "";
    const password = document.getElementById("password")?.value || "";

    // Input validation
    if (!username.trim()) {
        showToast("⚠️ Please enter your username/email", 'warning');
        showCustomAlert(
            "Missing Username", 
            "Please enter your username or email address to continue.", 
            'warning'
        );
        return;
    }

    if (!password.trim()) {
        showToast("⚠️ Please enter your password", 'warning');
        showCustomAlert(
            "Missing Password", 
            "Please enter your password to continue.", 
            'warning'
        );
        return;
    }

    // Show loading state
    showToast("🔄 Checking credentials...", 'info', 2000);

    try {
        // Simulate loading delay
        await new Promise(resolve => setTimeout(resolve, 1500));

        // Check credentials
        if (username === "gamingzone" && password === "gamingzone") {
            showToast("✅ Login successful! Redirecting to user dashboard...", 'success', 3000);
            await showCustomAlert(
                "Welcome Back!", 
                "Login successful! You will be redirected to the user dashboard.", 
                'success'
            );
            console.log('✅ User login successful, redirecting...');
            window.location.href = "../../user-interface/main.html";
            
        } else if (username === "admin" && password === "admin") {
            showToast("✅ Admin login successful! Redirecting to admin dashboard...", 'success', 3000);
            await showCustomAlert(
                "Admin Access Granted!", 
                "Welcome back, Administrator! You will be redirected to the admin dashboard.", 
                'success'
            );
            console.log('✅ Admin login successful, redirecting...');
            window.location.href = "../../admin-dashboard/index.html";
            
        } else {
            // Enhanced error handling for different scenarios
            let errorTitle = "Login Failed";
            let errorMessage = "";

            if (username.includes("@")) {
                // Email format detected
                errorMessage = `The email "${username}" is not registered in our system. Please check your email address or create a new account.`;
                errorTitle = "Email Not Found";
            } else {
                // Username format
                errorMessage = `The username "${username}" does not exist. Please check your username or create a new account.`;
                errorTitle = "Username Not Found";
            }

            // Show both toast and alert for login failure
            showToast("❌ Invalid credentials! User not found.", 'error', 5000);
            
            await showCustomAlert(
                errorTitle,
                errorMessage + "\n\nValid test credentials:\n• Username: gamingzone | Password: gamingzone\n• Username: admin | Password: admin",
                'error'
            );

            console.log('❌ Login failed for:', username);
            
            // Clear password field for security
            if (document.getElementById("password")) {
                document.getElementById("password").value = "";
            }
            
            // Focus on username field
            if (document.getElementById("username")) {
                document.getElementById("username").focus();
                document.getElementById("username").select();
            } else if (document.getElementById("email")) {
                document.getElementById("email").focus();
                document.getElementById("email").select();
            }
        }
        
    } catch (error) {
        console.error('❌ Login error:', error);
        showToast("❌ An error occurred during login. Please try again.", 'error');
        await showCustomAlert(
            "System Error",
            "An unexpected error occurred while processing your login. Please try again or contact support if the problem persists.",
            'error'
        );
    }
}

// Demo login function with enhanced feedback
async function demoLogin() {
    console.log('🎮 Starting demo login...');
    
    showToast("🎮 Setting up demo login...", 'info', 2000);
    
    try {
        // Fill demo credentials
        if (document.getElementById("username")) {
            document.getElementById("username").value = "gamingzone";
        }
        if (document.getElementById("email")) {
            document.getElementById("email").value = "gamingzone";
        }
        if (document.getElementById("password")) {
            document.getElementById("password").value = "gamingzone";
        }
        
        // Small delay to show the form fill
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        showToast("✅ Demo credentials loaded! Logging in...", 'success', 2000);
        
        // Trigger login
        await validateLogin();
        
    } catch (error) {
        console.error('❌ Demo login failed:', error);
        showToast("❌ Demo login failed. Please try manual login.", 'error');
        await showCustomAlert(
            "Demo Login Error",
            "The demo login feature encountered an error. Please try logging in manually with the credentials: gamingzone/gamingzone",
            'error'
        );
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Enhanced login script loaded');
    
    // Add Enter key support for login
    const passwordField = document.getElementById("password");
    if (passwordField) {
        passwordField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                validateLogin();
            }
        });
    }
    
    const usernameField = document.getElementById("username") || document.getElementById("email");
    if (usernameField) {
        usernameField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                validateLogin();
            }
        });
    }
});
