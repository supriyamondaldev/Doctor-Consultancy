// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupNavigation();
    setupFormValidation();
    setupDateTimeInputs();
    setupScrollAnimations();
    setupHeaderScrollEffect();
    setupModalHandlers();
}

// Smooth scrolling for navigation links
function setupNavigation() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = target.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Set minimum date to today and handle time slots
function setupDateTimeInputs() {
    const dateInput = document.getElementById('date');
    const timeSelect = document.getElementById('time');
    const doctorSelect = document.getElementById('doctor');
    
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        // Update available time slots when date or doctor changes
        dateInput.addEventListener('change', updateAvailableTimeSlots);
        if (doctorSelect) {
            doctorSelect.addEventListener('change', updateAvailableTimeSlots);
        }
    }
}

// Update available time slots based on selected doctor and date
async function updateAvailableTimeSlots() {
    const doctor = document.getElementById('doctor').value;
    const date = document.getElementById('date').value;
    const timeSelect = document.getElementById('time');
    
    if (!doctor || !date) return;
    
    try {
        const response = await fetch('php/get_available_slots.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ doctor, date })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear existing options except the first one
            timeSelect.innerHTML = '<option value="">Select time</option>';
            
            // Add available time slots
            data.slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot;
                option.textContent = formatTime(slot);
                timeSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error fetching available slots:', error);
    }
}

// Format time for display
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const time = new Date();
    time.setHours(parseInt(hours), parseInt(minutes));
    
    return time.toLocaleTimeString([], { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

// Form validation setup
function setupFormValidation() {
    const form = document.getElementById('bookingForm');
    if (!form) return;
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => clearFieldError(input));
    });
    
    // Form submission
    form.addEventListener('submit', handleFormSubmission);
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = `${getFieldLabel(fieldName)} is required`;
    }
    
    // Email validation
    if (fieldName === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (fieldName === 'phone' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }
    
    // Date validation
    if (fieldName === 'date' && value) {
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            isValid = false;
            errorMessage = 'Please select a future date';
        }
    }
    
    showFieldValidation(field, isValid, errorMessage);
    return isValid;
}

// Show field validation result
function showFieldValidation(field, isValid, errorMessage) {
    const fieldGroup = field.closest('.form-group');
    let errorElement = fieldGroup.querySelector('.error-message');
    
    // Remove existing validation classes
    field.classList.remove('error', 'success');
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        fieldGroup.appendChild(errorElement);
    }
    
    if (isValid) {
        field.classList.add('success');
        errorElement.classList.remove('show');
    } else {
        field.classList.add('error');
        errorElement.textContent = errorMessage;
        errorElement.classList.add('show');
    }
}

// Clear field error
function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.closest('.form-group').querySelector('.error-message');
    if (errorElement) {
        errorElement.classList.remove('show');
    }
}

// Get field label for error messages
function getFieldLabel(fieldName) {
    const labels = {
        firstName: 'First Name',
        lastName: 'Last Name',
        email: 'Email',
        phone: 'Phone Number',
        doctor: 'Doctor',
        consultationType: 'Consultation Type',
        date: 'Date',
        time: 'Time'
    };
    return labels[fieldName] || fieldName;
}

// Handle form submission
async function handleFormSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('submitBtn');
    
    // Validate all fields
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isFormValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isFormValid = false;
        }
    });
    
    if (!isFormValid) {
        showAlert('Please correct the errors above', 'error');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.textContent = 'Booking...';
    
    try {
        // Prepare form data
        const formData = new FormData(form);
        const bookingData = Object.fromEntries(formData);
        
        // Send booking request
        const response = await fetch('php/book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(bookingData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessModal(result);
            form.reset();
            clearAllValidation();
        } else {
            showErrorModal(result.message);
        }
        
    } catch (error) {
        console.error('Booking error:', error);
        showErrorModal('Network error. Please check your connection and try again.');
    } finally {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.textContent = 'Book Consultation';
    }
}

// Clear all form validation
function clearAllValidation() {
    const form = document.getElementById('bookingForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.classList.remove('error', 'success');
        const errorElement = input.closest('.form-group').querySelector('.error-message');
        if (errorElement) {
            errorElement.classList.remove('show');
        }
    });
}

// Show success modal
function showSuccessModal(result) {
    const modal = document.getElementById('successModal');
    const message = document.getElementById('successMessage');
    
    let successText = 'Your consultation has been successfully booked!';
    if (result.appointment_id) {
        successText += ` Your appointment ID is #${result.appointment_id.toString().padStart(6, '0')}.`;
    }
    if (result.email_sent) {
        successText += ' A confirmation email has been sent to you.';
    } else {
        successText += ' You will receive a confirmation call shortly.';
    }
    
    message.textContent = successText;
    modal.style.display = 'block';
}

// Show error modal
function showErrorModal(message) {
    const modal = document.getElementById('errorModal');
    const errorMessage = document.getElementById('errorMessage');
    
    errorMessage.textContent = message || 'An unexpected error occurred. Please try again.';
    modal.style.display = 'block';
}

// Show alert message
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Insert at top of form
    const form = document.getElementById('bookingForm');
    form.insertBefore(alert, form.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Modal handlers setup
function setupModalHandlers() {
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        const successModal = document.getElementById('successModal');
        const errorModal = document.getElementById('errorModal');
        
        if (e.target === successModal) {
            closeModal();
        }
        if (e.target === errorModal) {
            closeErrorModal();
        }
    });
    
    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeErrorModal();
        }
    });
}

// Modal control functions
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
}

function closeErrorModal() {
    document.getElementById('errorModal').style.display = 'none';
}

// Header background change on scroll
function setupHeaderScrollEffect() {
    window.addEventListener('scroll', () => {
        const header = document.querySelector('header');
        if (window.scrollY > 100) {
            header.style.backgroundColor = 'rgba(44, 90, 160, 0.95)';
            header.style.backdropFilter = 'blur(10px)';
        } else {
            header.style.backgroundColor = '';
            header.style.backdropFilter = '';
        }
    });
}

// Scroll animations setup
function setupScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all animated elements
    document.querySelectorAll('.service-card, .doctor-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// Utility function to format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Global functions for modal controls (accessible from HTML)
window.closeModal = closeModal;
window.closeErrorModal = closeErrorModal;