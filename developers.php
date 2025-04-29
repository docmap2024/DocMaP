<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Meet the developers behind DocMaP">
    <meta name="author" content="DocMaP Team">
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <title>DocMaP | Meet the Developers</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-digimedia-v2.css">
    <link rel="stylesheet" href="assets/css/animated.css">
    <link rel="stylesheet" href="assets/css/owl.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            text-align: center;
        }
        .team-section {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .developer-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .developer-card:hover {
            transform: scale(1.05);
        }
        .developer-img {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #ddd; /* Optional: adds a border around the circle */
        }

        .developer-info {
            margin-top: 15px;
        }
        .developer-name {
            font-size: 28px;
            font-weight: 600;
            color: #333;
        }
        .developer-role {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
       .social-icons {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }

    .social-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #9b2036;
        color: white;
        font-size: 20px;
        text-decoration: none;
        transition: 0.3s;
    }

    .social-circle:hover {
        background-color: #7A192A;
        color: #fff;
    }

    .bx {
        font-size: 22px;
    }
    </style>
</head>
<body>
  <!-- ***** Header Area Start ***** -->
  <header class="header-area header-sticky wow slideInDown" data-wow-duration="0.75s" data-wow-delay="0s">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <nav class="main-nav">
            <!-- ***** Logo Start ***** -->
            <a href="index.php" class="logo">
              <img src="img/Logo/docmap-logo-2.png" alt="" style="width: 200px;">
            </a>
            <!-- ***** Logo End ***** -->
            <!-- ***** Menu Start ***** -->
            <ul class="nav">
              <li class="scroll-to-section"><a href="index.php" >Home</a></li>
              <li class="scroll-to-section"><a href="index.php">About</a></li>
              <li class="scroll-to-section"><a href="index.php">Services</a></li>
              <li class="scroll-to-section"><a href="index.php">Memories</a></li>
              <li class="scroll-to-section"><a href="index.php">Contact</a></li> 
              <li class="scroll-to-section"><div class="border-first-button"><a href="index.php"id="loginBtn">LOGIN!</a></div></li> 
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
  <section class="team-section">
    <div class="container" style="margin-top:50px;">
        <h1 class="mb-4">Meet the Developers!</h1>
        <div class="row">
            <!-- Developer 1 -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="developer-card">
                    <img src="img/developers/SEAN.jpg" alt="Developer 1" class="developer-img">
                    <div class="developer-info">
                        <h5 class="developer-name">Sean Amorante</h5>
                        <p class="developer-role">Backend Developer</p>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/sean.amorante" target="_blank" class="social-circle"><i class='bx bxl-facebook'></i></a>
                            <a href="mailto:sean@example.com" class="social-circle"><i class='bx bx-envelope' ></i></a>
                            <a href="https://www.instagram.com/seanamoranteee/" target="_blank" class="social-circle"><i class='bx bxl-instagram'></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer 2 -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="developer-card">
                    <img src="img/developers/JAMIR PICTURE.jpg" alt="Developer 2" class="developer-img">
                    <div class="developer-info">
                        <h5 class="developer-name">Jamir Hernandez</h5>
                        <p class="developer-role">Developer/UI/UX Designer</p>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/jamir.a.hernandez" target="_blank" class="social-circle"><i class='bx bxl-facebook'></i></a>
                            <a href="mailto:jamiradrian.hernandez102602@gmail.com" class="social-circle"><i class='bx bx-envelope' ></i></a>
                            <a href="https://www.instagram.com/jamir.adrian/" target="_blank" class="social-circle"><i class='bx bxl-instagram'></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer 3 -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="developer-card">
                    <img src="img/developers/CHRISTIAN.jpg" alt="Developer 3" class="developer-img">
                    <div class="developer-info">
                        <h5 class="developer-name">Christian Abiog</h5>
                        <p class="developer-role">Backend Developer</p>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/christian.abiog.18" target="_blank" class="social-circle"><i class='bx bxl-facebook'></i></a>
                            <a href="mailto:christian@example.com" class="social-circle"><i class='bx bx-envelope' ></i></a>
                            <a href="https://www.instagram.com/chrstabiog/" target="_blank" class="social-circle"><i class='bx bxl-instagram'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Bootstrap JS (if needed) -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
