<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

$page_title = "Cricket Academy - Home";
require_once 'header.php';
?>

<section class="new-hero-section" data-aos="fade-in">
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="hero section.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content-new">
        <h1 data-aos="fade-down" data-aos-delay="200">Unleash Your Potential. Master the Game.</h1>
        <p data-aos="fade-up" data-aos-delay="400">Join our elite cricket academy and train with the best coaches to elevate your skills to the next level.</p>
        <div class="hero-buttons-new" data-aos="fade-up" data-aos-delay="600">
            <a href="register.php" class="btn btn-primary btn-lg">Enroll Today</a>
            <a href="login.php" class="btn btn-outline btn-lg">Existing Member? Login</a>
        </div>
    </div>
</section>
<section class="features-section">
    <div class="container">
        <h2 class="section-heading" data-aos="fade-down">Why Choose Our Academy?</h2>
        <div class="features-grid">
            <div class="feature-item" data-aos="fade-right" data-aos-delay="100">
                <i class="fas fa-user-tie"></i>
                <h3>Expert Coaching</h3>
                <p>Learn from a seasoned coach with years of professional experience dedicated to your growth.</p>
            </div>
            <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-chart-line"></i>
                <h3>Performance Tracking</h3>
                <p>Our portal allows you to track your stats, view performance history, and receive direct feedback.</p>
            </div>
            <div class="feature-item" data-aos="fade-left" data-aos-delay="300">
                <i class="fas fa-users"></i>
                <h3>Structured Batches</h3>
                <p>Join structured training batches tailored to specific skills and age groups for focused improvement.</p>
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
                    <div class="coach-image" data-aos="zoom-in">
                        <img src="murtaza.jpg" alt="Coach Murtaza - Head Coach" class="coach-img-style" onerror="this.onerror=null;this.src='https://placehold.co/600x400/cccccc/333333?text=Image+Not+Found';">
                    </div>
                    <div class="coach-text" data-aos="fade-right">
                        <h3>Murtaza Nahargarhwala</h3>
                        <p>With over 15 years of professional cricket experience and a passion for mentoring the next generation, Coach Murtaza brings a wealth of knowledge and dedication to the field. His coaching philosophy focuses on building strong fundamentals, mental toughness, and a true love for the game.</p>
                        <a href="login.php?user=coach" class="btn btn-secondary">Coach's Portal</a>
                    </div>
                </div>
            </div>

            <div class="coach-slide">
                <div class="coach-content">
                    <div class="coach-image">
                        <img src="altamas.jpg" alt="Coach Altamas Khan" class="coach-img-style" onerror="this.onerror=null;this.src='https://placehold.co/600x400/cccccc/333333?text=Image+Not+Found';">
                    </div>
                    <div class="coach-text">
                        <h3>Altamas Khan</h3>
                        <p>As a skilled tactician and certified coach, Altamas Khan specializes in developing the strategic aspects of the game. His modern approach helps players understand game awareness and improve their on-field decision-making under pressure.</p>
                        <a href="login.php?user=coach" class="btn btn-secondary">Coach's Portal</a>
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
                <div class="testimonial-image-container">
                     <img src="saad.jpg" alt="Saad Shah" onerror="this.onerror=null;this.src='https://placehold.co/100x100/cccccc/333333?text=User';">
                </div>
               <p>"The coaching here is top-notch. My batting average has improved significantly in just one season. The performance tracking portal is a huge plus!"</p>
                <h4>- Saad Shah</h4>
                <span>Senior Batch Player</span>
            </div>
            <div class="testimonial-card" data-aos="flip-up" data-aos-delay="200">
                <div class="testimonial-image-container">
                    <img src="mustafa.jpg" alt="Mustafa Attari" onerror="this.onerror=null;this.src='https://placehold.co/100x100/cccccc/333333?text=User';">
                </div>
                <p>"A fantastic and supportive environment for young cricketers to thrive. The structured batches make a real difference. Highly recommended!"</p>
                <h4>- Mustafa Attari</h4>
                 <span>Junior Batch Player</span>
            </div>
            <div class="testimonial-card" data-aos="flip-up" data-aos-delay="300">
                <div class="testimonial-image-container">
                    <img src="ahmad.jpg" alt="Ahmad Agaskar" onerror="this.onerror=null;this.src='https://placehold.co/100x100/cccccc/333333?text=User';">
                </div>
                <p>"The best academy I've been a part of. The focus on individual technique and strategy has prepared me for competitive matches."</p>
                <h4>- Ahmad Agaskar</h4>
                <span>U-19 Player</span>
            </div>
        </div>
    </div>
</section>

<section class="cta-section" data-aos="slide-up">
    <div class="container">
        <h2>Ready to Elevate Your Game?</h2>
        <p>Join a community of passionate cricketers and start your journey to excellence today.</p>
        <a href="register.php" class="btn btn-primary btn-lg" data-aos="zoom-in" data-aos-delay="200">Register for a Trial Session</a>
    </div>
</section>

<?php require_once 'footer.php'; ?>