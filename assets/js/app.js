// Menu Toggle per Header
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
        
        // Chiudi menu al click su link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        });
    }
    
    // Evidenzia bottom nav attiva
    const currentPath = window.location.pathname;
    document.querySelectorAll('.bottom-nav-item').forEach(item => {
        if (currentPath.includes(item.getAttribute('href'))) {
            item.classList.add('active');
        }
    });
});

// Timer per recupero
class RestTimer {
    constructor() {
        this.timeLeft = 0;
        this.interval = null;
        this.display = null;
    }
    
    start(seconds) {
        this.timeLeft = seconds;
        this.display = document.getElementById('timer-display');
        if (this.interval) clearInterval(this.interval);
        
        if (this.display) this.display.style.display = 'block';
        this.updateDisplay();
        
        this.interval = setInterval(() => {
            this.timeLeft--;
            this.updateDisplay();
            if (this.timeLeft <= 0) {
                this.stop();
                if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                if (this.display) {
                    this.display.style.color = 'var(--secondary)';
                    setTimeout(() => { this.display.style.color = ''; }, 2000);
                }
            }
        }, 1000);
    }
    
    stop() {
        if (this.interval) { clearInterval(this.interval); this.interval = null; }
    }
    
    updateDisplay() {
        if (this.display) {
            const minutes = Math.floor(this.timeLeft / 60);
            const seconds = this.timeLeft % 60;
            this.display.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }
}

const restTimer = new RestTimer();

// Toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
        background: ${type === 'success' ? 'var(--secondary)' : 'var(--danger)'};
        color: white; padding: 12px 24px; border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 1000;
        animation: fadeIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}