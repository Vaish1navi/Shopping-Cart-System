<?php
session_start();

require 'database/dbConnect.php';
require 'formValidation.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        
        $newPasswordPlain = bin2hex(random_bytes(4));
        $newPasswordHashed = password_hash($newPasswordPlain, PASSWORD_DEFAULT);

        
        $updateQuery = "UPDATE users SET password = '$newPasswordHashed' WHERE email = '$email'";
        $updateResult = mysqli_query($conn, $updateQuery);

        if ($updateResult) {
            
            $subject = "Password Reset - ShopEase";
            $body = "Hi,\n\nUser Name: $email\nYour new password is: $newPasswordPlain\n\nPlease login and change your password immediately for security.\n\nThank you.";
            $headers = "From: amarsahani0777@gmail.com";

            if (mail($email, $subject, $body, $headers)) {
                $message = "A new password has been sent to <strong>$email</strong>.";
            } else {
                $message = "Password updated, but failed to send email.";
            }
        } else {
            $message = "Error updating password.";
        }
    } else {
        $message = "No account found with this email.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <?php require 'essentials/commonLink.html'; ?>

</head>

<body>
    <?php require 'essentials/header.php'; ?>

    <main id="content" role="main" class="w-full  max-w-md mx-auto p-6">
        <div class="mt-7 bg-white  rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700 border-2 border-indigo-300">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Forgot password?</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Remember your password?
                        <a class="text-blue-600 decoration-2 hover:underline font-medium" href="login.php">
                            Login here
                        </a>
                    </p>
                </div>

                <div class="mt-5">
                    <form method="POST" action="forgetPassword.php">
                        <div class="grid gap-y-4">
                            <div>
                                <label for="email" class="block text-sm font-bold ml-1 mb-2 dark:text-white">Email address</label>
                                <div class="relative">
                                    <input type="email" id="email" name="email" class="py-3 px-4 block w-full border-2 border-gray-200 rounded-md text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm" required aria-describedby="email-error">
                                </div>
                                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
                            </div>
                            <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-2 rounded-md border border-transparent font-semibold bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all text-sm dark:focus:ring-offset-gray-800">Send password</button>
                        </div>
                    </form>
                    <?php if (!empty($message)) : ?>
                        <div class="text-center mt-4 text-sm font-medium text-[white]">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <p class="mt-3 flex justify-center items-center text-center divide-x divide-gray-300 dark:divide-gray-700">
            <a class="pl-3 inline-flex items-center gap-x-2 text-sm text-gray-600 decoration-2 hover:underline hover:text-blue-600 dark:text-gray-500 dark:hover:text-gray-200" href="#">
                Contact us!
            </a>
        </p>
    </main>
</body>

</html>