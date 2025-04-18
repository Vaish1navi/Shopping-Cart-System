<?php
session_start();

require 'database/dbConnect.php';
require 'formValidation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = sanitize_input($_POST['full_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);

    $password_changed = false;
    if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) < 8) {
                    $message = "Password must be at least 8 characters long.";
                    $message_type = "error";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_changed = true;
                }
            } else {
                $message = "New password and confirmation do not match.";
                $message_type = "error";
            }
        } else {
            $message = "Current password is incorrect.";
            $message_type = "error";
        }
    }

    if (empty($message)) {
        $update_sql = "UPDATE users SET full_name=?, last_name=?, email=?, phone=?";
        $params = [$full_name, $last_name, $email, $phone];
        $types = "ssss";
        
        if ($password_changed) {
            $update_sql .= ", password=?";
            $params[] = $hashed_password;
            $types .= "s";
        }
        
        $update_sql .= " WHERE id=?";
        $params[] = $user_id;
        $types .= "i";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['firstName'] = explode(' ', $full_name)[0];
            $_SESSION['email'] = $email;
            $message = "Profile updated successfully!";
            $message_type = "success";
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $message = "Error updating profile. Please try again.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Update | Your Website</title>
    <?php require 'essentials/commonLink.html'; ?>
    <style>
        .profile-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        .form-input {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        .form-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd1 0%, #6a3d9a 100%);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5e6778 0%, #424b5d 100%);
            transform: translateY(-2px);
        }
        .password-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .password-toggle:hover {
            color: #667eea;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require 'essentials/header.php'; ?>
    
    <div class="container mx-auto py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Update Your Profile</h1>
                <p class="text-gray-600">Manage your account information and security settings</p>
            </div>

            <?php if (!empty($message)) : ?>
                <div class="mb-6 p-4 rounded-md <?php 
                    echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 
                         ($message_type === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800');
                ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="profile-card p-6 md:p-8">
                <form action="account.php" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="full_name">First Name</label>
                            <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                type="text" id="full_name" name="full_name" 
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="last_name">Last Name</label>
                            <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                type="text" id="last_name" name="last_name" 
                                value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="email">Email</label>
                            <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                type="email" id="email" name="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="phone">Phone Number</label>
                            <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                type="text" id="phone" name="phone" 
                                value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2" for="address">Address</label>
                            <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                type="text" id="address" name="address" 
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Change Password</h3>
                        <p class="text-gray-600 mb-6">Leave these fields blank if you don't want to change your password.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="current_password">Current Password</label>
                                <div class="relative">
                                    <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                        type="password" id="current_password" name="current_password">
                                    <span class="absolute right-3 top-3.5 password-toggle" onclick="togglePassword('current_password')">
                                        👁️
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="new_password">New Password</label>
                                <div class="relative">
                                    <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                        type="password" id="new_password" name="new_password">
                                    <span class="absolute right-3 top-3.5 password-toggle" onclick="togglePassword('new_password')">
                                        👁️
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="confirm_password">Confirm Password</label>
                                <div class="relative">
                                    <input class="w-full px-4 py-3 form-input rounded-lg focus:outline-none" 
                                        type="password" id="confirm_password" name="confirm_password">
                                    <span class="absolute right-3 top-3.5 password-toggle" onclick="togglePassword('confirm_password')">
                                        👁️
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">Password must be at least 8 characters long.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8">
                        <a href="index.php" class="w-full sm:w-auto">
                            <button class="w-full px-6 py-3 btn-secondary rounded-lg text-white font-medium" type="button">
                                Cancel
                            </button>
                        </a>
                        <button class="w-full sm:w-auto px-6 py-3 btn-primary rounded-lg text-white font-medium" type="submit">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === "password" ? "text" : "password";
        }
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword.length < 8) {
                alert('Password must be at least 8 characters long');
                e.preventDefault();
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New password and confirmation do not match');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
