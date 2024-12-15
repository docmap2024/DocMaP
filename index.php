<?php
session_start();
$loginSuccess = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : null;
if ($loginSuccess !== null) {
    unset($_SESSION['login_success']); // Unset the session variable after use
}

include 'connection.php';

$schoolId = 1; // Replace with the actual School_ID or dynamic variable

// Query to fetch school details and join with school_mobile table to get Mobile_No
$query = "
    SELECT CONCAT(s.Address, ', ', s.City_Muni) AS FullAddress, s.Name, s.Email, m.Mobile_No 
    FROM school_details s
    INNER JOIN school_mobile m ON s.Mobile_ID = m.Mobile_ID
    WHERE s.school_details_ID = ?";

$stmt = $conn->prepare($query); // Assuming $conn is your database connection
$stmt->bind_param("i", $schoolId);
$stmt->execute();

// Bind the result variables in the correct order based on the SELECT query
$stmt->bind_result($address, $name, $email, $mobile);

// Fetch the data
$stmt->fetch();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <title>DocMaP</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">


    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-digimedia-v2.css">
    <link rel="stylesheet" href="assets/css/animated.css">
    <link rel="stylesheet" href="assets/css/owl.css">
 
 
  <style>
  /* Modal Styles */
  .modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 10; /* Very high z-index to ensure it appears above all other content */
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto; /* Allows scrolling if content inside the modal exceeds the viewport */
      background-color: rgba(0, 0, 0, 0.4); /* Semi-transparent background */
      display: flex;
      justify-content: center;
      align-items: center;
      pointer-events: auto; /* Ensures the modal catches pointer events */
  }


  .modal-content {
      background-color: #fefefe;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 800px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      border-radius: 10px;
      position: relative;
  }

  .close {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
  }

  .close:hover,
  .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
  }

  /* Illustration Styles */
  .illustration {
      display: flex;
      justify-content: center;
      align-items: center;
      padding-right: 30px;
  }

  .illustration img {
      max-width: 100%;
      height: auto;
  }

  /* Signup Form Styles */
  .signup-form {
      padding: 20px;
  }

  .signup-form h2 {
      font-size: 30px;
      margin-bottom: 20px;
      font-weight: bold;
  }

  .signup-form label {
      display: block;
      margin-bottom: 5px;
  }

  .signup-form input[type="text"],
  .signup-form input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
  }

  .signup-form input[type="submit"] {
      width: 100%;
      padding: 10px;
      background-color: #9B2035;
      color: white;
      border: none;
      border-radius: 90px;
      cursor: pointer;
  }

  .signup-form input[type="submit"]:hover {
      background-color: #7A172B;
  }

  .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
  }

  .password-wrapper input[type="password"] {
      width: 100%;
      padding-right: 50px;
  }

  .toggle-password {
      position: absolute;
      right: 10px;
      border: none;
      background: none;
      cursor: pointer;
      color: #9B2035;
      font-size: 16px;
      font-weight: bold;
      outline: none;
  }

  .forgot-password {
      margin-top: 5px;
      font-size: 14px;
      color: #9B2035;
      text-decoration: none;
  }

  .forgot-password:hover {
      text-decoration: underline;
      color: #9B2035;
  }

  .create-account {
      text-align: center;
      margin-top: 10px;
  }
  /* Scroll-up button styling */
.scroll-up {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 50px;
  height: 50px;
  background-color: #9B2035; /* Adjust color as needed */
  color: #fff;
  text-align: center;
  line-height: 50px;
  font-size: 24px;
  border-radius: 50%;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: opacity 0.3s ease, transform 0.3s ease;
  opacity: 0;
  visibility: hidden;
  transform: scale(0.9);
  z-index: 10;
}

/* Show button when visible */
.scroll-up.visible {
  opacity: 1;
  visibility: visible;
  transform: scale(1);
}

.scroll-up:hover {
  background-color: #B52A46; /* Darker shade on hover */
}

.scroll-up:hover i {
  animation: bounce 0.6s ease-in-out infinite; /* Apply bounce animation */
}

/* Keyframes for bounce effect */
@keyframes bounce {
  0%, 100% {
    transform: translateY(0); /* Original position */
  }
  50% {
    transform: translateY(-5px); /* Move up by 5px */
  }
}
.icons{
  font-size:20px;
  margin-right: 10px;
   height: auto;
   display: inline-block; 
}

      
  </style>
  </head>

<body>

  <!-- ***** Preloader Start ***** -->
  <div id="js-preloader" class="js-preloader">
    <div class="preloader-inner">
      <span class="dot"></span>
      <div class="dots">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </div>
  <!-- ***** Preloader End ***** -->



  <!-- ***** Header Area Start ***** -->
  <header class="header-area header-sticky wow slideInDown" data-wow-duration="0.75s" data-wow-delay="0s">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <nav class="main-nav">
            <!-- ***** Logo Start ***** -->
            <a href="#" class="logo">
              <img src="img/Logo/docmap-logo-2.png" alt="" style="width: 200px;">
            </a>
            <!-- ***** Logo End ***** -->
            <!-- ***** Menu Start ***** -->
            <ul class="nav">
              <li class="scroll-to-section"><a href="#top" class="active">Home</a></li>
              <li class="scroll-to-section"><a href="#about">About</a></li>
              <li class="scroll-to-section"><a href="#services">Services</a></li>
              <li class="scroll-to-section"><a href="#portfolio">Memories</a></li>
              <li class="scroll-to-section"><a href="#contact">Contact</a></li> 
              <li class="scroll-to-section"><div class="border-first-button"><a href="#"id="loginBtn">LOGIN!</a></div></li> 
            </ul>        
            <a class='menu-trigger'>
                <span>Menu</span>
            </a>
            <!-- ***** Menu End ***** -->
          </nav>
        </div>
      </div>
    </div>
  </header>
  <!-- ***** Header Area End ***** -->

<!-- Login Modal -->
<div id="loginModal" class="modal">
    <!-- Modal Content -->
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="container">
            <div class="row">
                <!-- Illustration Section -->
                <div class="col-md-6 illustration">
                    <img src="assets/images/login.png" alt="Illustration">
                </div>

                <!-- Signup Form Section -->
                <div class="col-md-6 signup-section">
                    <div class="signup-form">
                        <h2>LOGIN!</h2>
                        <form action="login.php" method="POST">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>

                            <label for="password">Password:</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" required>
                                <button type="button" id="togglePassword" class="toggle-password">Show</button>
                            </div>
                            <a href="forgot_pass.php" class="forgot-password">Forgot Password?</a>
                            <p id="capsLockWarning" style="color: #9b2035; display: none; margin-top: 5px; font-size: 12px; font-weight:bold;">
                                Warning: Caps Lock is on!
                            </p>

                            <input type="submit" value="Log in" class="border-first-button " style=" margin-top: 20px;">
                        </form>

                        <div class="create-account">
                            <a href="Register.php" style ="color:#9B2035;">Create an account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>









  <div class="main-banner wow fadeIn" id="top" data-wow-duration="1s" data-wow-delay="0.5s">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="row">
            <div class="col-lg-6 align-self-center">
              <div class="left-content show-up header-text wow fadeInLeft" data-wow-duration="1s" data-wow-delay="1s">
                <div class="row">
                  <div class="col-lg-12">
                  <h6 style="color:#9B2035;">
                    Welcome to<img src="img/Logo/docmap.png" alt="DocMaP Logo" style="width:90px;height:auto;margin-bottom:5px;margin-left:5px;">!
                  </h6>

                    <h2>Managing Documents Is What We Do Best! </h2>
                    <p>
                    Welcome to DocMaP, the cutting-edge document management portal designed to streamline and modernize your document handling experience. Effortlessly organize, access, and collaborate on your files with our innovative solutions tailored for today's fast-paced world.</p>
                  </div>
                  <div class="col-lg-12">
                    <div class="border-first-button scroll-to-section">
                      <a href="Register.php"> <i class='bx bxs-user-plus icons'></i>Register!</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="right-image wow fadeInRight" data-wow-duration="1s" data-wow-delay="0.5s" >
                <img src="assets/images/yuh.png" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="about" class="about section">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="row">
            <div class="col-lg-6">
              <div class="about-left-image  wow fadeInLeft" data-wow-duration="1s" data-wow-delay="0.5s">
                <img src="assets/images/About.png" alt="">
              </div>
            </div>
            <div class="col-lg-6 align-self-center  wow fadeInRight" data-wow-duration="1s" data-wow-delay="0.5s">
              <div class="about-right-content">
                <div class="section-heading">
                  <h6>About Us</h6>
                  <h4>What does <img src="img/Logo/docmap.png" alt="DocMaP Logo" style="width:130px;height:auto;margin-bottom:5px;margin-left:5px;"> do?</h4>
                  <div class="line-dec"></div>
                </div>
                <p>
                At DocMaP, we are dedicated to revolutionizing document management by offering a modern, user-friendly platform that simplifies the way you handle your documents. Our mission is to empower individuals and businesses with efficient, innovative solutions for seamless organization, access, and collaboration.</p>      
                </div> 
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="services" class="services section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="section-heading wow fadeInDown" data-wow-duration="1s" data-wow-delay="0.5s">
          <h6>Our Services</h6>
          <h4>What <img src="img/Logo/docmap.png" alt="DocMaP Logo" style="width:130px;height:auto;margin-bottom:5px;margin-left:5px;"> Offers</h4>
          <div class="line-dec"></div>
        </div>
      </div>
      <div class="col-lg-12">
        <div class="naccs">
          <div class="grid">
            <div class="row">
              <div class="col-lg-12">
                <div class="menu">
                  <div class="first-thumb active">
                    <div class="thumb" style ="font-size:16px;">
                      <span class="icon"><img src="assets/images/storage.png" alt=""></span>
                      Document Storage
                    </div>
                  </div>
                  <div>
                    <div class="thumb"  style ="font-size:16px;">
                      <span class="icon"><img src="assets/images/orgtools.png" alt=""></span>
                      Organization Tools
                    </div>
                  </div>
                  <div>
                    <div class="thumb"  style ="font-size:16px;">
                      <span class="icon"><img src="assets/images/secure.png" alt=""></span>
                      Secure Sharing
                    </div>
                  </div>
                  <div>
                    <div class="thumb"  style ="font-size:16px;">
                      <span class="icon"><img src="assets/images/access.png" alt=""></span>
                      Access Anywhere
                    </div>
                  </div>
                  <div class="last-thumb">
                    <div class="thumb"  style ="font-size:16px;">
                      <span class="icon"><img src="assets/images/collab.png" alt=""></span>
                      Collaboration
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-12">
                <ul class="nacc">
                  <li class="active">
                    <div>
                      <div class="thumb">
                        <div class="row">
                          <div class="col-lg-6 align-self-center">
                            <div class="left-text">
                              <h4>Centralized Document Storage</h4>
                              <p>Store all your important documents in one secure location with DocMap's cloud-based storage solutions, ensuring easy access and reliability.</p>
                              <div class="ticks-list">
                                <span><i class="fa fa-check"></i> High Security</span>
                                <span><i class="fa fa-check"></i> Scalable Storage</span>
                                <span><i class="fa fa-check"></i> Easy Retrieval</span>
                              </div>
                              <p>Say goodbye to scattered files and hello to streamlined management.</p>
                            </div>
                          </div>
                          <div class="col-lg-6 align-self-center">
                            <div class="right-image">
                            <img src="assets/images/services-image.jpg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li>
                    <div>
                      <div class="thumb">
                        <div class="row">
                          <div class="col-lg-6 align-self-center">
                            <div class="left-text">
                              <h4>Smart Organization Tools</h4>
                              <p>Organize your documents using advanced categorization and tagging tools for seamless navigation and workflow optimization.</p>
                              <div class="ticks-list">
                                <span><i class="fa fa-check"></i> Tagging & Categorization</span>
                                <span><i class="fa fa-check"></i> Custom Folders</span>
                                <span><i class="fa fa-check"></i> Intuitive Search</span>
                              </div>
                              <p>Find what you need in seconds with smart filters and sorting options.</p>
                            </div>
                          </div>
                          <div class="col-lg-6 align-self-center">
                            <div class="right-image">
                              <img src="assets/images/services-image-02.jpg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li>
                    <div>
                      <div class="thumb">
                        <div class="row">
                          <div class="col-lg-6 align-self-center">
                            <div class="left-text">
                              <h4>Secure Document Sharing</h4>
                              <p>Share sensitive documents securely with colleagues or clients using encrypted links and advanced access controls.</p>
                              <div class="ticks-list">
                                <span><i class="fa fa-check"></i> Encrypted Transfers</span>
                                <span><i class="fa fa-check"></i> Access Control</span>
                                <span><i class="fa fa-check"></i> Expiry Links</span>
                              </div>
                              <p>Collaborate without compromising privacy and security.</p>
                            </div>
                          </div>
                          <div class="col-lg-6 align-self-center">
                            <div class="right-image">
                              <img src="assets/images/services-image-03.jpg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li>
                    <div>
                      <div class="thumb">
                        <div class="row">
                          <div class="col-lg-6 align-self-center">
                            <div class="left-text">
                              <h4>Access Anytime, Anywhere</h4>
                              <p>With DocMap, your documents are available on any device, ensuring you're always prepared no matter where you are.</p>
                              <div class="ticks-list">
                                <span><i class="fa fa-check"></i> Mobile-Friendly</span>
                                <span><i class="fa fa-check"></i> Cross-Device Sync</span>
                                <span><i class="fa fa-check"></i> Offline Access</span>
                              </div>
                              <p>Stay connected with your files even when you're on the move.</p>
                            </div>
                          </div>
                          <div class="col-lg-6 align-self-center">
                            <div class="right-image">
                              <img src="assets/images/services-image-04.jpg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li>
                    <div>
                      <div class="thumb">
                        <div class="row">
                          <div class="col-lg-6 align-self-center">
                            <div class="left-text">
                              <h4>Team Collaboration</h4>
                              <p>Work together effortlessly with real-time editing, version control, and activity logs to keep your projects on track.</p>
                              <div class="ticks-list">
                                <span><i class="fa fa-check"></i> Real-Time Collaboration</span>
                                <span><i class="fa fa-check"></i> Version History</span>
                                <span><i class="fa fa-check"></i> User Activity Logs</span>
                              </div>
                              <p>Empower your team with tools that streamline collaboration and productivity.</p>
                            </div>
                          </div>
                          <div class="col-lg-6 align-self-center">
                            <div class="right-image">
                              <img src="assets/images/services-image-02.jpg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>          
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  
  


  <div id="portfolio" class="our-portfolio section">
    <div class="container">
      <div class="row">
        <div class="col-lg-5">
          <div class="section-heading wow fadeInLeft" data-wow-duration="1s" data-wow-delay="0.3s">
            <h6>Our Photos</h6>
            <h4>See Our <em>Memories</em></h4>
            <h6><i class='bx bxs-school icon' style="margin-right: 10px;"></i><?php echo htmlspecialchars($name); ?></h6>
            <div class="line-dec"></div>
          </div>
        </div>
      </div>
    </div>

<div class="container-fluid wow fadeIn" data-wow-duration="1s" data-wow-delay="0.7s">
    <div class="row">
        <div class="col-lg-12">
            <div class="loop owl-carousel">
                <?php
                include 'connection.php'; // Include your database connection file

                // Fetch the number of photos from the database
                $sql = "SELECT COUNT(photo_name) AS photo_count FROM school_photos";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $photoCount = $row['photo_count'];

                    // Fetch photos to display
                    $sql = "SELECT photo_name FROM school_photos LIMIT $photoCount";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $photoName = $row['photo_name'];
                            echo '
                            <div class="item">
                                <a href="#">
                                    <div class="portfolio-item">
                                        <div class="thumb">
                                            <img src="assets/School_Images/' . $photoName . '" alt="">
                                        </div>
                                        <div class="down-content">
                                            <!-- Additional content can go here if needed -->
                                        </div>
                                    </div>
                                </a>
                            </div>';
                        }
                    } else {
                        echo 'No photos found in the database.';
                    }
                } else {
                    echo 'Error counting photos.';
                }

                $conn->close();
                ?>
            </div>
        </div>
    </div>
</div>




  </div>
  
  
  <div id="contact" class="contact-us section">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 offset-lg-3">
        <div class="section-heading wow fadeIn" data-wow-duration="1s" data-wow-delay="0.5s">
          <h6>Contact Us</h6>
          <h4>Get In Touch With Us <em>Now</em></h4>
          <div class="line-dec"></div>
        </div>
      </div>
      <div class="col-lg-12 wow fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.25s">
        <form id="contact" action="" method="post">
          <div class="row">
            <div class="col-lg-12">
              <div class="contact-dec">
                <img src="assets/images/contact-dec-v2.png" alt="">
              </div>
            </div>
            <div class="col-lg-5">
              <div id="map">
                <!-- Google Map Embed Code -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7741.345043380841!2d120.64763249357911!3d14.03741570000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd965943d50dc7%3A0x9577638105912c15!2s307706-Lian%20National%20High%20School!5e0!3m2!1sen!2sph!4v1721712955046!5m2!1sen!2sph" width="100%" height="636px" frameborder="0" style="border:0" allowfullscreen></iframe>              </div>
            </div>
            <div class="col-lg-7">
              <div class="fill-form">
                <div class="row">
                  <div class="col-lg-4">
                    <div class="info-post">
                      <div class="icon">
                        <img src="assets/images/phone-icon.png" alt="">
                        <a href="tel:<?php echo htmlspecialchars($mobile); ?>"><?php echo htmlspecialchars($mobile); ?></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="info-post">
                      <div class="icon">
                        <img src="assets/images/email-icon.png" alt="">
                        <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="info-post">
                      <div class="icon">
                        <img src="assets/images/location-icon.png" alt="">
                        <a href="https://www.google.com/maps/search/<?php echo urlencode($address); ?>" target="_blank"><?php echo htmlspecialchars($address); ?></a>
                      </div>
                    </div>
                  </div>
                  <!-- Additional Form Fields -->
                  <div class="col-lg-6">
                    <fieldset>
                      <input type="name" name="name" id="name" placeholder="Name" autocomplete="on" required>
                    </fieldset>
                    <fieldset>
                      <input type="text" name="email" id="email" pattern="[^ @]*@[^ @]*" placeholder="Your Email" required="">
                    </fieldset>
                    <fieldset>
                      <input type="subject" name="subject" id="subject" placeholder="Subject" autocomplete="on">
                    </fieldset>
                  </div>
                  <div class="col-lg-6">
                    <fieldset>
                      <textarea name="message" type="text" class="form-control" id="message" placeholder="Message" required=""></textarea>  
                    </fieldset>
                  </div>
                  <div class="col-lg-12">
                    <fieldset>
                      <button type="submit" id="form-submit" class="main-button ">Send Message Now</button>
                    </fieldset>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div id="scrollUpButton" class="scroll-up">
<i class='bx bx-chevrons-up'></i>
</div>



  <footer>
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <p>Copyright © 2024 DocMaP by QWERTY Co., Ltd. All Rights Reserved. 
          <br>
          <a href="https://github.com/AbiAb1/ProfTal.git" target="_parent">
            <img src="img/Logo/qwerty.png" alt="QWERTY Logo" style = "width: 150px;">
          </a>
          </p>
        </div>
      </div>
    </div>
  </footer>
<!-- Caps Lock Warning -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const passwordInput = document.getElementById("password");
        const capsLockWarning = document.getElementById("capsLockWarning");

        passwordInput.addEventListener("keyup", function (event) {
            // Show or hide the Caps Lock warning
            capsLockWarning.style.display = event.getModifierState("CapsLock") ? "block" : "none";
        });
    });
</script>

<!-- Modal Management -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("loginModal");
        const btn = document.getElementById("loginBtn");
        const closeModal = document.querySelector("#loginModal .close");

        // Open modal on button click
        btn.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent link behavior
            modal.style.display = "flex";
        });

        // Close modal on close button click
        closeModal.addEventListener("click", function () {
            modal.style.display = "none";
        });

        // Close modal when clicking outside it
        window.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });
</script>

<!-- Show/Hide Password -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const passwordInput = document.getElementById("password");
        const togglePasswordButton = document.getElementById("togglePassword");

        togglePasswordButton.addEventListener("click", function () {
            // Toggle between password and text input types
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePasswordButton.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                togglePasswordButton.textContent = "Show";
            }
        });
    });
</script>

<!-- Scroll-Up Button -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const scrollUpButton = document.getElementById("scrollUpButton");

        // Show/hide button based on scroll position
        window.addEventListener("scroll", () => {
            scrollUpButton.classList.toggle("visible", window.scrollY > 800);
        });

        // Scroll to the top on button click
        scrollUpButton.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    });
</script>

<!-- SweetAlert Login Error -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        <?php if (isset($loginSuccess) && $loginSuccess === false): ?>
        Swal.fire({
            title: "Login Failed",
            text: "Invalid username or password.",
            icon: "error",
            confirmButtonText: "Try Again"
        });
        <?php endif; ?>
    });
</script>

<!-- Additional External Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/owl-carousel.js"></script>
<script src="assets/js/animation.js"></script>
<script src="assets/js/imagesloaded.js"></script>
<script src="assets/js/custom.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>