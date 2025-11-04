</main>
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <p>&copy; <?= date('Y') ?> Cricket Academy. All rights reserved.</p>
                    <div class="social-links">
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script>
          AOS.init({
            duration: 800,
            once: true,
          });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slides = document.querySelectorAll('.coach-slide');
                if (slides.length > 0) {
                    let currentSlide = 0;
                    setInterval(() => {
                        slides[currentSlide].classList.remove('active-slide');
                        // Move to the next slide, or loop back to the first
                        currentSlide = (currentSlide + 1) % slides.length;
                        slides[currentSlide].classList.add('active-slide');
                    }, 3000); // Change slide every 3 seconds (3000 milliseconds)
                }
            });
        </script>
    </body>
</html>