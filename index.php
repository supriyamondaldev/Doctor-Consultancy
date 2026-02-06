<?php
// Include database configuration
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get doctors from database
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM doctors ORDER BY name");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $doctors = [];
    error_log("Error fetching doctors: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediConsult - Professional Healthcare Services</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="container">
            <div class="logo">MediConsult</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#doctors">Doctors</a></li>
                <li><a href="#booking">Book Now</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="#booking" class="cta-btn">Book Consultation</a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <h1>Your Health, Our Priority</h1>
            <p>Connect with certified healthcare professionals from the comfort of your home. Get expert medical consultation with our experienced doctors.</p>
            <div class="hero-buttons">
                <a href="#booking" class="btn-primary">Book Consultation</a>
                <a href="#services" class="btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card fade-in-up">
                    <span class="service-icon">🩺</span>
                    <h3>General Consultation</h3>
                    <p>Comprehensive health checkups and medical advice from experienced general practitioners.</p>
                </div>
                <div class="service-card fade-in-up">
                    <span class="service-icon">❤️</span>
                    <h3>Cardiology</h3>
                    <p>Expert cardiac care and consultation for heart-related conditions and prevention.</p>
                </div>
                <div class="service-card fade-in-up">
                    <span class="service-icon">🧠</span>
                    <h3>Neurology</h3>
                    <p>Specialized neurological consultations for brain and nervous system disorders.</p>
                </div>
                <div class="service-card fade-in-up">
                    <span class="service-icon">👶</span>
                    <h3>Pediatrics</h3>
                    <p>Dedicated healthcare services for infants, children, and adolescents.</p>
                </div>
                <div class="service-card fade-in-up">
                    <span class="service-icon">🦴</span>
                    <h3>Orthopedics</h3>
                    <p>Bone, joint, and muscle care with advanced treatment options.</p>
                </div>
                <div class="service-card fade-in-up">
                    <span class="service-icon">👁️</span>
                    <h3>Ophthalmology</h3>
                    <p>Complete eye care services including vision tests and treatments.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="doctors" class="doctors">
        <div class="container">
            <h2 class="section-title">Meet Our Doctors</h2>
            <div class="doctors-grid">
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card">
                    <div class="doctor-image">
                        <?php echo (strpos($doctor['name'], 'Dr.') === 0 && strpos(strtolower($doctor['name']), 'sarah') !== false) ? '👩‍⚕️' : '👨‍⚕️'; ?>
                    </div>
                    <div class="doctor-info">
                        <h4><?php echo htmlspecialchars($doctor['name']); ?></h4>
                        <p class="specialization"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <div class="rating">
                            <?php
                            $rating = $doctor['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= floor($rating) ? '⭐' : '';
                            }
                            echo " ($rating)";
                            ?>
                        </div>
                        <p><?php echo $doctor['experience_years']; ?>+ years of experience</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking" class="booking">
        <div class="container">
            <h2 class="section-title">Book Your Consultation</h2>
            <form class="booking-form" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="doctor">Select Doctor</label>
                        <select id="doctor" name="doctor" required>
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['name'] . ' - ' . $doctor['specialization']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="consultationType">Consultation Type</label>
                        <select id="consultationType" name="consultationType" required>
                            <option value="">Select type</option>
                            <option value="video">Video Call</option>
                            <option value="phone">Phone Call</option>
                            <option value="in-person">In-Person</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Preferred Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Preferred Time</label>
                        <select id="time" name="time" required>
                            <option value="">Select time</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="09:30">9:30 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="10:30">10:30 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="11:30">11:30 AM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="14:30">2:30 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="15:30">3:30 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="16:30">4:30 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="symptoms">Describe Your Symptoms/Concern</label>
                    <textarea id="symptoms" name="symptoms" rows="4" placeholder="Please describe your symptoms or reason for consultation..."></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;" id="submitBtn">
                    Book Consultation
                </button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>MediConsult</h4>
                    <p>Providing quality healthcare consultation services with experienced medical professionals.</p>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p>📧 info@mediconsult.com</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>📍 123 Medical Plaza, Healthcare City</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <p><a href="#home">Home</a></p>
                    <p><a href="#services">Services</a></p>
                    <p><a href="#doctors">Our Doctors</a></p>
                    <p><a href="#booking">Book Now</a></p>
                </div>
                <div class="footer-section">
                    <h4>Working Hours</h4>
                    <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                    <p>Saturday: 9:00 AM - 6:00 PM</p>
                    <p>Sunday: 10:00 AM - 4:00 PM</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 MediConsult. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 style="color: #10b981; text-align: center; margin-bottom: 1rem;">✅ Booking Confirmed!</h2>
            <p style="text-align: center; margin-bottom: 1.5rem;" id="successMessage">Your consultation has been successfully booked. You will receive a confirmation email shortly.</p>
            <div style="text-align: center;">
                <button class="btn-primary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeErrorModal()">&times;</span>
            <h2 style="color: #ef4444; text-align: center; margin-bottom: 1rem;">❌ Booking Failed</h2>
            <p style="text-align: center; margin-bottom: 1.5rem;" id="errorMessage">An error occurred while booking your appointment. Please try again.</p>
            <div style="text-align: center;">
                <button class="btn-primary" onclick="closeErrorModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>