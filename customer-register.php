<!DOCTYPE html>
 <html lang="en">
     <head>
         <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Customer Registration | TechConnect</title> 
           <link rel="stylesheet" href="style.css">
            <style>
             .customer-register-section {
                 background: #f4f8ff;
              padding: 50px 20px;
               min-height: 80vh; } 
             .customer-register-card { max-width: 650px; 
             margin: auto; 
             padding: 35px;
              background: #fff;
               border-radius: 16px;
                border: 1px solid #e2e8f0;
                 box-shadow: 0 8px 25px rgba(18, 59, 112, 0.08);
            }
            .form-heading {
                 text-align: center;
                  margin-bottom: 30px;
            } 
            .form-heading h1 {
                 color: #123b70;
                  margin-bottom: 10px;
                   font-size: 30px;
            } .form-heading p {
                 color: #64748b;
                  line-height: 1.6;
            }
             .customer-form .form-group { margin-bottom: 20px; } .customer-form label { display: block; color: #334155; font-weight: 600; margin-bottom: 8px; } .customer-form input, .customer-form textarea { width: 100%; box-sizing: border-box; padding: 13px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: inherit; outline: none; } .customer-form input:focus, .customer-form textarea:focus { border-color: #1769d2; box-shadow: 0 0 0 3px rgba(23, 105, 210, 0.12); } .customer-form textarea { resize: vertical; min-height: 90px; } .customer-submit { width: 100%; border: none; border-radius: 8px; padding: 15px; background: #1769d2; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; } .customer-submit:hover { background: #0f4fa8; } .form-message { display: none; margin-top: 20px; padding: 14px; border-radius: 8px; line-height: 1.5; } .form-message.error { display: block; background: #fee2e2; color: #991b1b; } .form-message.success { display: block; background: #dcfce7; color: #166534; } .form-links { text-align: center; margin-top: 25px; color: #64748b; } .form-links a { color: #1769d2; text-decoration: none; font-weight: 600; } @media (max-width: 600px) { .customer-register-section { padding: 30px 15px; } .customer-register-card { padding: 25px 18px; } .form-heading h1 { font-size: 25px; } } </style> </head> <body> <!-- Navigation --> <header class="navbar"> <div class="container nav-container"> <a href="index.php" class="logo">TechConnect</a> <nav> <a href="index.php">Home</a> <a href="technicians.php">Find Technicians</a> <a href="login.php">Login</a> </nav> </div> </header> <!-- Customer Registration Form --> <main class="customer-register-section"> <div class="customer-register-card"> <div class="form-heading"> <h1>Create Customer Account</h1> <p> Enter your details to get started with TechConnect. </p> </div> <form class="customer-form" id="customerRegisterForm"> <div class="form-group"> <label for="full_name">Full Name</label> <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" autocomplete="name" required > </div> <div class="form-group"> <label for="email">Email Address</label> <input type="email" id="email" name="email" placeholder="example@email.com" autocomplete="email" required > </div> <div class="form-group"> <label for="phone">Phone Number</label> <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" autocomplete="tel" required > </div> <div class="form-group"> <label for="address">Location / Address</label> <textarea id="address" name="address" placeholder="Enter your town or address" autocomplete="street-address" required ></textarea> </div> <div class="form-group"> <label for="password">Password</label> <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required > </div> <div class="form-group"> <label for="confirm_password">Confirm Password</label> <input type="password" id="confirm_password" name="confirm_password" placeholder="Enter your password again" minlength="8" autocomplete="new-password" required > </div> <button type="submit" class="customer-submit"> Create Customer Account </button> <div id="formMessage" class="form-message" role="status" aria-live="polite" ></div> </form> <div class="form-links"> Already registered? <a href="login.php">Log in</a> <br><br> <a href="register.php">← Back to account selection</a> </div> </div> </main> <!-- Footer --> <footer class="footer"> <div class="container"> <p> &copy; <?php echo date("Y"); ?> TechConnect. All rights reserved. </p> </div> </footer>
              <script> const customerForm = document.getElementById("customerRegisterForm"); const formMessage = document.getElementById("formMessage"); customerForm.addEventListener("submit", function(event) { event.preventDefault(); const password = document.getElementById("password").value; const confirmPassword = document.getElementById("confirm_password").value; formMessage.className = "form-message"; formMessage.textContent = ""; if (password !== confirmPassword) { formMessage.textContent = "Your passwords do not match. Please try again."; formMessage.classList.add("error"); return; } formMessage.textContent = "Form validation successful! Account creation will be enabled when the backend is connected."; formMessage.classList.add("success"); }); </script> </body> </html>