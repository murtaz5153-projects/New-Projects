<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

if (isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'active') {
    header("Location: dashboard.php");
    exit();
}

$page_title = "Join the Academy";
require_once 'header.php';
?>

<div class="subscribe-page-wrapper">

    <section class="subscribe-page-section" style="padding-top: 20px;">
        <div class="container">
            <i class="fas fa-star" style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;"></i>
            <h1 style="font-size: 2.8rem; color: var(--primary-color);">Your Journey Starts Here</h1>
            <p style="font-size: 1.2rem; color: var(--text-color-light); max-width: 700px; margin: 0 auto 2.5rem auto;">
                Activate your account to unlock the full potential of the Cricket Academy portal and elevate your game.
            </p>
            <div class="subscription-gallery">
                <div class="gallery-item" data-aos="fade-up">
                    <img src="Cricket/KidsPlaying.jpg" alt="Students learning from a coach">
                    <div class="caption">Professional Group Training</div>
                </div>
                 <div class="gallery-item" data-aos="fade-up" data-aos-delay="100">
                    <img src="Cricket/Wicket.jpg" alt="Wicketkeeper practicing">
                    <div class="caption">Personalized Skill Development</div>
                </div>
                 <div class="gallery-item" data-aos="fade-up" data-aos-delay="200">
                    <img src="Cricket/WarmUP.jpg" alt="Students warming up">
                    <div class="caption">Structured Fitness & Drills</div>
                </div>
                <div class="gallery-item" data-aos="fade-up" data-aos-delay="300">
                    <img src="Cricket/Ground.jpg" alt="Cricket ground and equipment">
                    <div class="caption">Access to Pro-Level Facilities</div>
                </div>
            </div>
        </div>
    </section>

    <section class="subscribe-page-section">
        <div class="container">
            <h2 class="section-heading">What You'll Unlock</h2>
            <div class="benefits-grid">
                <div class="benefit-card" data-aos="fade-right">
                    <i class="fas fa-user-tie"></i>
                    <h3>Expert Coaching</h3>
                    <p>Get direct access to our professional coaching staff for personalized feedback and guidance.</p>
                </div>
                <div class="benefit-card" data-aos="fade-up">
                    <i class="fas fa-chart-line"></i>
                    <h3>Performance Tracking</h3>
                    <p>Monitor your stats, view match history, and track your improvement over time.</p>
                </div>
                <div class="benefit-card" data-aos="fade-left">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Custom Schedules</h3>
                    <p>Access your personalized training and match schedules anytime, anywhere.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="coach-section">
        <div class="container">
            <h2 class="section-heading" data-aos="fade-down">Meet the Coaches</h2>
            <div class="coach-slider-container">
                <div class="coach-slide active-slide">
                    <div class="coach-content">
                        <div class="coach-image" data-aos="zoom-in"><img src="murtaza.jpg" alt="Coach Murtaza" class="coach-img-style"></div>
                        <div class="coach-text" data-aos="fade-right">
                            <h3>Murtaza Nahargarhwala</h3>
                            <p>With over 15 years of professional experience, Coach Murtaza focuses on building strong fundamentals and mental toughness.</p>
                        </div>
                    </div>
                </div>
                <div class="coach-slide">
                    <div class="coach-content">
                        <div class="coach-image"><img src="altamas.jpg" alt="Coach Altamas" class="coach-img-style"></div>
                        <div class="coach-text">
                            <h3>Altamas Khan</h3>
                            <p>As a skilled tactician, Coach Altamas specializes in developing strategic game awareness and on-field decision-making.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-heading" data-aos="fade-down">What Our Players Say</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card" data-aos="flip-up" data-aos-delay="100">
                    <div class="testimonial-image-container"><img src="saad.jpg" alt="Saad Shah"></div>
                    <p>"The coaching here is top-notch. My batting average has improved significantly in just one season."</p>
                    <h4>- Saad Shah</h4><span>Senior Batch Player</span>
                </div>
                <div class="testimonial-card" data-aos="flip-up" data-aos-delay="200">
                    <div class="testimonial-image-container"><img src="mustafa.jpg" alt="Mustafa Attari"></div>
                    <p>"A fantastic and supportive environment for young cricketers to thrive. Highly recommended!"</p>
                    <h4>- Mustafa Attari</h4><span>Junior Batch Player</span>
                </div>
                <div class="testimonial-card" data-aos="flip-up" data-aos-delay="300">
                    <div class="testimonial-image-container"><img src="ahmad.jpg" alt="Ahmad Agaskar"></div>
                    <p>"The best academy I've been a part of. The focus on individual technique has prepared me for matches."</p>
                    <h4>- Ahmad Agaskar</h4><span>U-19 Player</span>
                </div>
            </div>
        </div>
    </section>

    <section class="subscribe-page-section">
        <div class="container">
            <div class="final-cta" data-aos="zoom-in">
                <h2>Ready to Activate Your Membership?</h2>
                <p>Join a community of passionate cricketers and start your journey to excellence today.</p>
                <div class="price">Rs4999 / month</div>
                <form action="payment.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="btn btn-primary btn-lg">Subscribe and Unlock All Features</button>
                </form>
            </div>
        </div>
    </section>

</div>

<?php 
// We add a custom main.container class removal for this specific page in the footer
// The footer will be included as normal.
$no_main_container = true; 
require_once 'footer.php'; 
?>