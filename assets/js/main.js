/**
 * Kanadierrennen CJD Kaltenstein - Main JavaScript
 */

// Dokument bereit
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

function init() {
    // Auto-Refresh für öffentliche Seiten
    initAutoRefresh();
    
    // Mobile Navigation
    initMobileNav();
    
    // Formular-Validierung
    initFormValidation();
    
    // CSRF-Token für AJAX-Anfragen
    initCSRFToken();
    
    // Zeit-Formatierung
    initTimeFormatting();
    
    // Tooltips
    initTooltips();
    
    // Tabs
    initTabs();
    
    // Accordion
    initAccordion();
    
    // Console Log
    console.log('Kanadierrennen Webanwendung - Initialisiert');
}

/**
 * Auto-Refresh für öffentliche Seiten
 */
function initAutoRefresh() {
    const metaRefresh = document.querySelector('meta[http-equiv="refresh"]');
    if (metaRefresh) {
        // Meta-Refresh ist bereits gesetzt
        return;
    }
    
    // Für Seiten mit Echtzeit-Daten
    const isPublicPage = document.body.classList.contains('public-page');
    const isResultsPage = window.location.pathname.includes('ergebnisse.php') || 
                         window.location.pathname.includes('index.php');
    const isStartTimesPage = window.location.pathname.includes('startzeiten.php');
    
    if ((isPublicPage && isResultsPage) || (isPublicPage && isStartTimesPage)) {
        // Auto-Refresh alle 60 Sekunden
        setTimeout(function() {
            location.reload();
        }, 60000);
    }
}

/**
 * Mobile Navigation
 */
function initMobileNav() {
    const navToggle = document.createElement('button');
    navToggle.className = 'nav-toggle';
    navToggle.innerHTML = '<span class="nav-toggle-icon">☰</span>';
    navToggle.style.cssText = `
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
    `;
    
    const headerNav = document.querySelector('.header-nav');
    if (headerNav) {
        headerNav.prepend(navToggle);
    }
    
    const navList = document.querySelector('.nav-list');
    if (navList) {
        navToggle.addEventListener('click', function() {
            navList.style.display = navList.style.display === 'flex' ? 'none' : 'flex';
        });
    }
    
    // Responsive Check
    function checkResponsive() {
        if (window.innerWidth <= 768) {
            navToggle.style.display = 'block';
            navList.style.display = 'none';
        } else {
            navToggle.style.display = 'none';
            navList.style.display = 'flex';
        }
    }
    
    checkResponsive();
    window.addEventListener('resize', checkResponsive);
}

/**
 * Formular-Validierung
 */
function initFormValidation() {
    // Zeit-Validierung (HH:MM:SS)
    const timeInputs = document.querySelectorAll('input[type="text"][name="time"]');
    timeInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/;
            
            if (value && !timeRegex.test(value)) {
                this.classList.add('error');
                showError(this, 'Bitte geben Sie eine gültige Zeit ein (HH:MM:SS)');
            } else {
                this.classList.remove('error');
                hideError(this);
            }
        });
    });
    
    // E-Mail-Validierung
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value && !emailRegex.test(value)) {
                this.classList.add('error');
                showError(this, 'Bitte geben Sie eine gültige E-Mail-Adresse ein');
            } else {
                this.classList.remove('error');
                hideError(this);
            }
        });
    });
    
    // Formular-Submit mit Validierung
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const hasErrors = this.querySelectorAll('.error').length > 0;
            if (hasErrors) {
                e.preventDefault();
                alert('Bitte korrigieren Sie die markierten Fehler.');
            }
        });
    });
}

/**
 * Fehler anzeigen
 */
function showError(input, message) {
    hideError(input);
    
    const error = document.createElement('div');
    error.className = 'form-error';
    error.textContent = message;
    error.style.cssText = `
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    `;
    
    input.parentNode.appendChild(error);
}

/**
 * Fehler ausblenden
 */
function hideError(input) {
    const error = input.parentNode.querySelector('.form-error');
    if (error) {
        error.remove();
    }
}

/**
 * CSRF-Token für AJAX-Anfragen
 */
function initCSRFToken() {
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    if (csrfToken) {
        window.CSRF_TOKEN = csrfToken.value;
    }
}

/**
 * Zeit-Formatierung
 */
function initTimeFormatting() {
    // Zeit-Eingabefeld mit Platzhalter
    const timeInputs = document.querySelectorAll('input[type="text"][name="time"]');
    timeInputs.forEach(input => {
        if (!input.value) {
            input.placeholder = 'HH:MM:SS';
        }
        
        // Auto-Formatierung
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + ':' + value.substring(2);
            }
            if (value.length >= 5) {
                value = value.substring(0, 5) + ':' + value.substring(5, 7);
            }
            if (value.length > 8) {
                value = value.substring(0, 8);
            }
            
            this.value = value;
        });
    });
}

/**
 * Tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = this.getAttribute('data-tooltip');
            const rect = this.getBoundingClientRect();
            
            // Tooltip erstellen
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'custom-tooltip';
            tooltipElement.textContent = tooltip;
            tooltipElement.style.cssText = `
                position: absolute;
                top: ${rect.top - 40}px;
                left: ${rect.left + (rect.width / 2) - 50}px;
                background: #333;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 4px;
                font-size: 0.875rem;
                z-index: 1000;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltipElement);
            
            element.addEventListener('mouseleave', function() {
                tooltipElement.remove();
            }, { once: true });
        });
    });
}

/**
 * Tabs
 */
function initTabs() {
    const tabContainers = document.querySelectorAll('.tabs');
    tabContainers.forEach(container => {
        const tabs = container.querySelectorAll('.tab');
        const contents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Alle Tabs deaktivieren
                tabs.forEach(t => t.classList.remove('active'));
                
                // Alle Inhalte ausblenden
                contents.forEach(c => c.classList.remove('active'));
                
                // Aktiven Tab markieren
                this.classList.add('active');
                
                // Entsprechenden Inhalt anzeigen
                const targetId = this.getAttribute('data-target');
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    });
}

/**
 * Accordion
 */
function initAccordion() {
    const accordions = document.querySelectorAll('.accordion');
    accordions.forEach(accordion => {
        const header = accordion.querySelector('.accordion-header');
        
        header.addEventListener('click', function() {
            accordion.classList.toggle('active');
        });
    });
}

/**
 * AJAX-Hilfsfunktionen
 */
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    if (window.CSRF_TOKEN) {
        xhr.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN);
    }
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            if (callback) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(null, xhr.responseText);
                }
            }
        } else {
            if (callback) {
                callback(new Error('Request failed'), null);
            }
        }
    };
    
    xhr.onerror = function() {
        if (callback) {
            callback(new Error('Network error'), null);
        }
    };
    
    if (data) {
        const params = new URLSearchParams(data);
        xhr.send(params.toString());
    } else {
        xhr.send();
    }
}

/**
 * Benachrichtigung anzeigen
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8'};
        color: white;
        border-radius: 4px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Nach Duration entfernen
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

/**
 * Zeit in Sekunden umrechnen
 */
function timeToSeconds(time) {
    const [h, m, s] = time.split(':');
    return (parseInt(h) * 3600) + (parseInt(m) * 60) + parseInt(s);
}

/**
 * Sekunden in Zeit umrechnen
 */
function secondsToTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

/**
 * Zeit addieren
 */
function addTime(time1, time2) {
    return secondsToTime(timeToSeconds(time1) + timeToSeconds(time2));
}

/**
 * Zeit subtrahieren
 */
function subtractTime(time1, time2) {
    const diff = timeToSeconds(time1) - timeToSeconds(time2);
    return secondsToTime(Math.abs(diff));
}

// CSS-Animationen für Benachrichtigungen
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export für Module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        init,
        ajaxRequest,
        showNotification,
        timeToSeconds,
        secondsToTime,
        addTime,
        subtractTime
    };
}
