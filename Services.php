    <?php require_once('header.php'); ?>

    <!-- App Download Section with Modern Design -->
    <div class="app-download-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="app-info">
                        <h1 class="app-title">Get Our AR Mobile App</h1>
                        <p class="app-description">Download our application for a seamless shopping experience on your mobile device. Access exclusive deals, track orders, and shop anywhere, anytime.</p>
                        <div class="download-btn-wrapper">
                            <a href="downloads/ARPlant.apk" download class="download-btn">
                                <i class="fa fa-android"></i> Download App
                            </a>
                            <p class="download-note">📲 How to Download and Use Our AR App (Android Only)</p>
                            <p class="download-note">1. Click the “Download” button on our website to go directly to the app in the Google Play Store.</p>
                            <p class="download-note">2. Tap the green Install button.</p>
                            <p class="download-note">3. Wait for the app to download and install on your phone.</p>
                            <p class="download-note">4. Once installed, tap Open to launch the app.</p>
                            <p class="download-note">5. When prompted, allow camera and location access so the AR features can work properly.</p>
                            <p class="download-note">6. Follow the on-screen instructions to scan your space and start placing plants in AR!</p>
                            <p class="download-note">7. Browse, rotate, and move plants to see how they look in your home before buying.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="app-showcase">
                        <div id="appCarousel" class="carousel slide" data-ride="carousel">
                            <ol class="carousel-indicators">
                                <li data-target="#appCarousel" data-slide-to="0" class="active"></li>
                                <li data-target="#appCarousel" data-slide-to="1"></li>
                                <li data-target="#appCarousel" data-slide-to="2"></li>
                            </ol>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="assets/img/controls.png" class="d-block" alt="App Screenshot 1">
                                    <div class="carousel-caption">
                                        <h5>User-Friendly Interface</h5>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/img/controls.png" class="d-block" alt="App Screenshot 2">
                                    <div class="carousel-caption">
                                        <h5>Shopping Cart</h5>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/img/icon.png" class="d-block" alt="App Screenshot 3">
                                    <div class="carousel-caption">
                                        <h5>Product Browsing</h5>
                                    </div>
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#appCarousel" role="button" data-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#appCarousel" role="button" data-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                        <div class="phone-frame"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Section -->
    <div class="qr-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3 text-center">
                    <h3>Scan to Download</h3>
                    <p>Use your phone camera to scan this QR code and download our app directly.</p>
                    <div class="qr-code">
                        <!-- Replace with actual QR code image that links to your APK -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://yourwebsite.com/downloads/your-app.apk" alt="QR Code">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="carousel">
    <div class="carousel-container">
    <h1 class="landscape-title">Malou's Garden Landscape Services Project</h1>
    <div class="carousel-images">
    <img src="assets/img/1.jpg" alt="Image 1" />
        <img src="assets/img/2.jpg" alt="Image 2" />
        <img src="assets/img/4.jpg" alt="Image 4" />
        <img src="assets/img/5.jpg" alt="Image 5" />
        <img src="assets/img/6.jpg" alt="Image 6" />
        <img src="assets/img/7.jpg" alt="Image 7" />
        <img src="assets/img/8.jpg" alt="Image 8" />
    </div>
    <div class="buttons">
        <button onclick="moveSlide(-1)">❮</button>
        <button onclick="moveSlide(1)">❯</button>
    </div>
    </div>

      <header>
    <h1>GreenVision Landscapes</h1>
    <p>Bringing Your Garden Vision to Life with Augmented Reality</p>
  </header>


  <section class="services">
    <h2>Our Services</h2>
    <ul>
      <li><strong>Landscape Design & Installation</strong><br>Custom layouts, planting plans, hardscaping, irrigation, and lighting.</li>
      <li><strong>AR Garden Visualization</strong><br>View your future garden in 3D using our augmented reality tools.</li>
      <li><strong>Seasonal Garden Maintenance</strong><br>Year-round care: pruning, pest control, and seasonal updates.</li>
      <li><strong>Smart Garden Integration</strong><br>Automated irrigation, smart lighting, and weather-based controls.</li>
    </ul>
  </section>

   <footer>
    <p>
      📍 Maimpis, City of San Fernando, Pampanga<br>
    </p>

    <div style="margin-top: 20px;">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d11085.51788201168!2d-97.7391511!3d30.2671531!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8644b5a6c3e2d51f%3A0x2e4d1ef3c1fbd89e!2s123%20Verde%20Way%2C%20Austin%2C%20TX!5e0!3m2!1sen!2sus!4v1686525262067!5m2!1sen!2sus" 
        width="100%" 
        height="300" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </footer>

    <script>
    let index = 0;

    function moveSlide(step) {
        const carousel = document.querySelector('.carousel-images');
        const totalImages = carousel.children.length;
        const containerWidth = document.querySelector('.carousel-container').clientWidth;

        index += step;
        if (index < 0) index = totalImages - 1;
        if (index >= totalImages) index = 0;

        carousel.style.transform = `translateX(-${index * containerWidth}px)`;
    }

    window.addEventListener('resize', () => moveSlide(0));
    </script>



    <!-- CSS for the App Download Page -->
    <style>
        /* Main App Download Section */
        .app-download-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }

        .landscape-title{
            text-align: center;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .app-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .app-description {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #34495e;
        }
        
        /* Download Button */
        .download-btn-wrapper {
            margin-bottom: 30px;
        }
        
        .download-btn {
            display: inline-block;
            background: #4A5568;
            color: white;
            font-size: 18px;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .download-btn:hover {
            background: #2D3748;
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            color: white;
            text-decoration: none;
        }
        
        .download-btn i {
            margin-right: 10px;
        }
        
        .download-note {
            font-size: 14px;
            color: #718096;
            margin-top: 10px;
        }
        
        /* App Features */
        .app-features {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .feature-item {
            text-align: center;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            width: 30%;
        }
        
        .feature-item i {
            font-size: 24px;
            color: #3182CE;
            margin-bottom: 10px;
            display: block;
        }
        
        /* App Showcase / Carousel */
        .app-showcase {
            position: relative;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .carousel {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .carousel-item img {
            height: 520px;
            width: 100%;
            object-fit: cover;
        }
        
        .phone-frame {
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            border: 10px solid #2c3e50;
            border-radius: 40px;
            pointer-events: none;
            z-index: 1;
        }
        
        /* QR Code Section */
        .qr-section {
            padding: 40px 0;
            background-color: white;
        }
        
        .qr-code {
            margin: 20px auto;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .qr-code img {
            max-width: 100%;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .app-title {
                font-size: 32px;
            }
            
            .app-features {
                flex-direction: column;
            }
            
            .feature-item {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .app-showcase {
                margin-top: 40px;
            }
        }

            .carousel {
        position: relative;
        width: 100%; /* Width of one image */
        height: 80%; /* Set this to match your image height */
        overflow: hidden;
        margin: auto;
        border: 2px solid #ccc;
        border-radius: 10px;
        }

    .carousel-container {
    width: 100%;
    overflow: hidden;
    position: relative;
    }

    .carousel-images {
    display: flex;
    transition: transform 0.5s ease-in-out;
    width: 100%;
    }

    .carousel-images img {
    width: 100%;
    height: 700px;
    object-fit: cover;
    flex-shrink: 0;
    border: 5px solid black; /* Bold border added */
    padding-bottom: 50px;
    }

    .buttons {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
    padding: 0 20px;
    box-sizing: border-box;
    pointer-events: none; /* Let clicks pass through except for buttons */
    }

    .buttons button {
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    font-size: 30px;
    padding: 10px 20px;
    cursor: pointer;
    pointer-events: all; /* Re-enable pointer events on the button itself */
    }

    
    header {
      background: #5c8a4c;
      color: white;
      padding: 20px;
      text-align: center;
    }

    section {
      padding: 40px 20px;
      max-width: 1000px;
      margin: auto;
    }

    h1, h2 {
      color: #4b6f3a;
    }

    .services ul {
      list-style: none;
      padding: 0;
    }

    .services li {
      background: #e3f0e3;
      margin-bottom: 15px;
      padding: 15px;
      border-left: 5px solid #5c8a4c;
    }

    footer {
      background: #e0eae0;
      text-align: center;
      padding: 20px;
      font-size: 0.9em;
    }

    @media (max-width: 600px) {
      section {
        padding: 20px 10px;
      }
    }
    </style>

    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Include Bootstrap CSS and JS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <?php require_once('footer.php'); ?>