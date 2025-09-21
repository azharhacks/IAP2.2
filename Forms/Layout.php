<?php
/**
 * FormRenderer class - Handles HTML form generation and layout
 * OOP Principles: Single Responsibility, Static Methods, Encapsulation
 */
class FormRenderer {
    
    // OOP: Static method - can be called without instantiation
    public static function renderSignupForm($error = '', $success = '') {
        return self::renderPageLayout('Signup', self::buildSignupFormHTML($error, $success)); // OOP: Static method call
    }
    
    // OOP: Private static method (Encapsulation) - builds the form HTML
    private static function buildSignupFormHTML($error, $success) {
        $errorHTML = $error ? "<p style='color:red;'>" . htmlspecialchars($error) . "</p>" : '';
        $successHTML = $success ? "<p style='color:green;'>" . htmlspecialchars($success) . "</p>" : '';
        
        return "
            <div class='form-container'>
                <h2>Create Your Account</h2>
                {$errorHTML}
                {$successHTML}
                <form method='POST' class='signup-form'>
                    <div class='form-group'>
                        <label for='email'>Email Address:</label>
                        <input type='email' id='email' name='email' placeholder='Enter your email' required>
                    </div>
                    <div class='form-group'>
                        <label for='password'>Password:</label>
                        <input type='password' id='password' name='password' placeholder='Minimum 6 characters' required>
                    </div>
                    <button type='submit' class='submit-btn'>Sign Up</button>
                </form>
                <p class='link-text'>Already have an account? <a href='Signin.php'>Sign In</a></p>
            </div>
        ";
    }
    
    // OOP: Private static method (Encapsulation) - creates the complete page layout
    private static function renderPageLayout($title, $content) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title} - Auth System</title>
            <style>
                " . self::getPageStyles() . "
            </style>
        </head>
        <body>
            <div class='page-wrapper'>
                {$content}
            </div>
        </body>
        </html>";
    }
    
    // OOP: Private static method (Encapsulation) - returns CSS styles
    private static function getPageStyles() {
        return "
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .page-wrapper { width: 100%; max-width: 400px; padding: 20px; }
            .form-container { 
                background: white; padding: 40px; border-radius: 10px; 
                box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            }
            h2 { color: #333; margin-bottom: 30px; text-align: center; }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
            input[type='email'], input[type='password'] { 
                width: 100%; padding: 12px; border: 2px solid #e1e1e1; 
                border-radius: 5px; font-size: 16px; transition: border-color 0.3s;
            }
            input[type='email']:focus, input[type='password']:focus { 
                outline: none; border-color: #667eea; 
            }
            .submit-btn { 
                width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 12px; border: none; border-radius: 5px; 
                font-size: 16px; cursor: pointer; transition: transform 0.3s;
            }
            .submit-btn:hover { transform: translateY(-2px); }
            .link-text { text-align: center; margin-top: 20px; color: #666; }
            .link-text a { color: #667eea; text-decoration: none; }
            .link-text a:hover { text-decoration: underline; }
            p[style*='color:red'] { 
                background: #fee; border: 1px solid #fcc; padding: 10px; 
                border-radius: 5px; margin-bottom: 15px; 
            }
            p[style*='color:green'] { 
                background: #efe; border: 1px solid #cfc; padding: 10px; 
                border-radius: 5px; margin-bottom: 15px; 
            }
        ";
    }
}
?>