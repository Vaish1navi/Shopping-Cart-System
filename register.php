<?php

session_start();
require 'database/dbConnect.php';
require 'formValidation.php';


ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $firstName = $_POST['first-name'];
    $lastName = $_POST['last-name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $phone = $_POST['phone'];
    $terms = isset($_POST['terms']) ? true : false;
    
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($phone)) {
        $error = "All fields are required";
    } elseif (!$terms) {
        $error = "You must agree to the terms and conditions";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already exists";
        } else {
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $fullName = $firstName . ' ' . $lastName;
            
            
            $stmt = $conn->prepare("INSERT INTO users (full_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                $error = "Error preparing statement: " . $conn->error;
            } else {
                
                $bindResult = $stmt->bind_param('sssss', $fullName, $lastName, $email, $hashedPassword, $phone);
                
                if ($bindResult === false) {
                    $error = "Error binding parameters: " . $stmt->error;
                } else {
                    
                    if ($stmt->execute()) {
                        
                        $user_id = $stmt->insert_id;
                        
                        
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['email'] = $email;
                        $full_name = $fullName;
                        $parts = explode(' ', $full_name);
                        $first_name = $parts[0];
                        $_SESSION['firstName'] = $first_name;
                        
                        
                        $_SESSION['success'] = "Registration successful! Welcome to our site.";
                        
                        
                        session_regenerate_id(true);
                        
                        
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
        $check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase - Create Account</title>
    <?php require 'essentials/commonLink.html' ?>
    
    <style>
        .signup-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
        }
        .password-strength {
            height: 4px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">ShopEasy</a>
            <nav>
                <ul class="flex space-x-6">
                    <li><a href="index.php" class="text-gray-700 hover:text-blue-600">Home</a></li>
                    <li><a href="login.php" class="text-gray-700 hover:text-blue-600">Sign In</a></li>
                </ul>
            </nav>
        </div>
    </header>

    
    <main>
        <div class="flex flex-col md:flex-row min-h-screen">
            
            <div class="w-full md:w-1/2 flex items-center justify-center p-8">
                <div class="w-full max-w-md">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Create Your Account</h1>
                        <p class="text-gray-600">Join our community today</p>
                    </div>

                
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    
                    <?php if (!empty($success)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $success; ?></span>
                        </div>
                    <?php endif; ?>

                    
                    <form method="POST" action="register.php" class="bg-white rounded-lg shadow-md p-8">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="first-name" class="block text-gray-700 font-medium mb-2">First Name</label>
                                <input type="text" id="first-name" name="first-name" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="John" required>
                            </div>
                            <div>
                                <label for="last-name" class="block text-gray-700 font-medium mb-2">Last Name</label>
                                <input type="text" id="last-name" name="last-name" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Doe" required>
                            </div>
                        </div>

                       
                        <div class="mb-6">
                            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                placeholder="123-456-7890" required>
                        </div>


                        
                        <div class="mb-6">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="far fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="you@example.com" required>
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="••••••••" required
                                    oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1">
                                        <div id="password-strength-bar" class="password-strength h-1 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span id="password-strength-text" class="ml-2 text-xs text-gray-500">Weak</span>
                                </div>
                                <ul class="mt-2 text-xs text-gray-500 list-disc list-inside">
                                    <li id="length-requirement">At least 8 characters</li>
                                    <li id="number-requirement">Contains a number</li>
                                    <li id="special-requirement">Contains a special character</li>
                                </ul>
                            </div>
                        </div>

                        
                        <div class="mb-6">
                            <label for="confirm-password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="confirm-password" name="confirm-password" 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="••••••••" required>
                            </div>
                            <p id="password-match" class="hidden text-xs mt-1 text-red-500">Passwords do not match</p>
                        </div>

                        
                        <div class="mb-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox" 
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        required>
                                </div>
                                <label for="terms" class="ml-2 block text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
                                </label>
                            </div>
                        </div>


                        
                        <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Create Account
                        </button>

                        
                        <div class="mt-6">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Or sign up with</span>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fab fa-google text-red-500 mr-2"></i> Google
                                </a>
                                <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fab fa-facebook-f text-blue-600 mr-2"></i> Facebook
                                </a>
                            </div>
                        </div>
                    </form>

                    
                    <div class="mt-8 text-center">
                        <p class="text-gray-600">Already have an account? 
                            <a href="login.php" class="text-blue-600 font-medium hover:underline">Sign in</a>
                        </p>
                    </div>
                </div>
            </div>

           
            <div class="hidden md:block w-1/2 signup-bg">
                <div class="h-full flex items-center justify-center p-12">
                    <div class="text-white text-center max-w-lg">
                        <h2 class="text-4xl font-bold mb-4">Welcome to ShopEase</h2>
                        <p class="text-xl mb-6">Join thousands of happy customers enjoying our exclusive member benefits.</p>
                        <ul class="text-left space-y-3 mb-8">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                                <span>Exclusive member discounts</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                                <span>Faster checkout process</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                                <span>Personalized recommendations</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                                <span>Order tracking history</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    
    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2023 ShopEasy. All rights reserved.</p>
            <div class="mt-4 flex justify-center space-x-6">
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </footer>

    <script>
        
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

       
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            const lengthReq = document.getElementById('length-requirement');
            const numberReq = document.getElementById('number-requirement');
            const specialReq = document.getElementById('special-requirement');
            
            
            lengthReq.style.color = '';
            numberReq.style.color = '';
            specialReq.style.color = '';
            
            let strength = 0;
            
            
            if (password.length >= 8) {
                strength += 1;
                lengthReq.style.color = '#10B981';
            }
            
            
            if (/\d/.test(password)) {
                strength += 1;
                numberReq.style.color = '#10B981';
            }
            
            
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                strength += 1;
                specialReq.style.color = '#10B981';
            }
            
            
            const width = (strength / 3) * 100;
            strengthBar.style.width = width + '%';
            
            
            if (width < 40) {
                strengthBar.style.backgroundColor = '#EF4444'; 
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#EF4444';
            } else if (width < 80) {
                strengthBar.style.backgroundColor = '#F59E0B'; 
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#F59E0B';
            } else {
                strengthBar.style.backgroundColor = '#10B981'; 
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#10B981';
            }
        }

        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchMessage = document.getElementById('password-match');
            
            if (confirmPassword && password !== confirmPassword) {
                matchMessage.classList.remove('hidden');
            } else {
                matchMessage.classList.add('hidden');
            }
        });
    </script>
</body>
</html>