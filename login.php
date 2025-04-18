<?php
session_start();

require 'database/dbConnect.php';
require 'formValidation.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        
        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $full_name = $user['full_name'];
            $parts = explode(' ', $full_name);
            $first_name = $parts[0];
            $_SESSION['firstName'] = $first_name;

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No user found with that email!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase - Login</title>
    <?php require 'essentials/commonLink.html' ?>
    
    <style>
        .login-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
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
                    <li><a href="register.php" class="text-gray-700 hover:text-blue-600">Create Account</a></li>
                </ul>
            </nav>
        </div>
    </header>

    
    <main>
        <div class="flex flex-col md:flex-row min-h-screen">
            
            <div class="w-full md:w-1/2 flex items-center justify-center p-8">
                <div class="w-full max-w-md">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
                        <p class="text-gray-600">Sign in to access your account</p>
                    </div>

                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    
                    <form method="POST" action="login.php" class="bg-white rounded-lg shadow-md p-8">
                        
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

                        
                        <div class="mb-6">
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="••••••••" required>
                                <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <input id="remember" name="remember" type="checkbox" 
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                            </div>
                            <div>
                                <a href="forgot-password.php" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                            </div>
                        </div>

                        
                        <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Sign In
                        </button>

                        
                        <div class="mt-6">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Or continue with</span>
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
                        <p class="text-gray-600">Don't have an account? 
                            <a href="register.php" class="text-blue-600 font-medium hover:underline">Sign up</a>
                        </p>
                    </div>
                </div>
            </div>

            
            <div class="hidden md:block w-1/2 login-bg">
                <div class="h-full flex items-center justify-center p-12">
                    <div class="text-white text-center max-w-lg">
                        <h2 class="text-4xl font-bold mb-4">New to ShopEasy?</h2>
                        <p class="text-xl mb-8">Join our community and discover amazing products with exclusive member benefits.</p>
                        <a href="register.php" class="inline-block bg-white text-blue-600 font-medium py-3 px-8 rounded-lg hover:bg-gray-100 transition">Create Account</a>
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
    </script>
</body>
</html>