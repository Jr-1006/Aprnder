<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private $mail;
    private $config;
    private $debug;
    
    public function __construct($debug = false) {
        $this->debug = $debug;
        
        $this->config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'mjraquino2@tip.edu.ph', // Replace with your Gmail
            'password' => 'tmba pkaz ursi vphp', // Replace with your Gmail App Password (no spaces)
            'from_email' => 'mjraquino2@tip.edu.ph',
            'from_name' => 'Aprender Game System',
            'smtp_secure' => PHPMailer::ENCRYPTION_STARTTLS,
            'smtp_auth' => true,
            'smtp_debug' => $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF
        ];
        
        $this->mail = new PHPMailer(true);
        $this->setupMailer();
    }
    
    private function setupMailer() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = $this->config['smtp_auth'];
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['port'];
            $this->mail->SMTPDebug = $this->config['smtp_debug'];
            
            // Additional SMTP options for better compatibility
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Set timeout
            $this->mail->Timeout = 30;
            
            // Recipients
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            
            if ($this->debug) {
                error_log("MailService setup completed successfully");
            }
            
        } catch (Exception $e) {
            error_log("MailService setup error: " . $e->getMessage());
            if ($this->debug) {
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            throw $e;
        }
    }
    
    public function sendRegistrationConfirmation($email, $name) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $name);
            
            $this->mail->Subject = 'Welcome to Aprender - Registration Confirmed!';
            
            $body = $this->getRegistrationEmailBody($name);
            $this->mail->Body = $body;
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Registration confirmation sent to: " . $email);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Registration email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPasswordReset($email, $name, $resetToken) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $name);
            
            $this->mail->Subject = 'Aprender - Password Reset Request';
            
            $resetUrl = "http://localhost/Websys/public/reset-password.php?token=" . $resetToken;
            $body = $this->getPasswordResetEmailBody($name, $resetUrl);
            $this->mail->Body = $body;
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Password reset email sent to: " . $email);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendGameScoreNotification($email, $name, $score, $wave) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $name);
            
            $this->mail->Subject = 'Aprender - New High Score Achievement!';
            
            $body = $this->getGameScoreEmailBody($name, $score, $wave);
            $this->mail->Body = $body;
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Game score notification sent to: " . $email);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Game score email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendContactForm($name, $email, $subject, $message) {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            // Send to admin/support email
            $this->mail->addAddress($this->config['from_email'], 'Aprender Support');
            // Add reply-to as the sender
            $this->mail->addReplyTo($email, $name);
            
            $this->mail->Subject = 'Contact Form: ' . $subject;
            
            $body = $this->getContactFormEmailBody($name, $email, $subject, $message);
            $this->mail->Body = $body;
            
            if ($this->debug) {
                error_log("Attempting to send contact form email to: " . $this->config['from_email']);
                error_log("From: " . $email . " (" . $name . ")");
                error_log("Subject: " . $subject);
            }
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Contact form submitted by: " . $email . " - Subject: " . $subject);
            } else {
                error_log("Contact form send failed but no exception thrown");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Contact form email error: " . $e->getMessage());
            if ($this->debug) {
                error_log("Full error details: " . print_r($e, true));
                error_log("SMTP Error Info: " . $this->mail->ErrorInfo);
            }
            return false;
        }
    }
    
    private function getRegistrationEmailBody($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎮 Welcome to Aprender!</h1>
                    <p>Your Tower Defense Learning Adventure Begins</p>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($name) . "!</h2>
                    <p>Welcome to <strong>Aprender</strong> - the gamified learning platform that combines programming education with an exciting tower defense game!</p>
                    
                    <h3>What you can do:</h3>
                    <ul>
                        <li>🎯 Play our interactive Tower Defense game</li>
                        <li>📚 Learn programming concepts through gameplay</li>
                        <li>🏆 Compete on the leaderboard</li>
                        <li>📊 Track your progress and achievements</li>
                    </ul>
                    
                    <p>Ready to start your learning journey?</p>
                    <a href='http://localhost/Websys/public/game.php' class='button'>Start Playing Now!</a>
                    
                    <p>If you have any questions, feel free to contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>Aprender - Gamified Problem-Based Learning System</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetEmailBody($name, $resetUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #ff6b6b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                    <p>Aprender Account Security</p>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($name) . "!</h2>
                    <p>We received a request to reset your password for your Aprender account.</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Important:</strong> If you did not request this password reset, please ignore this email. Your account remains secure.
                    </div>
                    
                    <p>To reset your password, click the button below:</p>
                    <a href='" . htmlspecialchars($resetUrl) . "' class='button'>Reset My Password</a>
                    
                    <p><strong>This link will expire in 24 hours for security reasons.</strong></p>
                    
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($resetUrl) . "</p>
                </div>
                <div class='footer'>
                    <p>Aprender - Gamified Problem-Based Learning System</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getGameScoreEmailBody($name, $score, $wave) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #00b894 0%, #00a085 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .score-display { background: #00b894; color: white; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0; }
                .button { display: inline-block; background: #00b894; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏆 New High Score!</h1>
                    <p>Amazing Achievement in Aprender</p>
                </div>
                <div class='content'>
                    <h2>Congratulations " . htmlspecialchars($name) . "!</h2>
                    <p>You've achieved an impressive score in the Aprender Tower Defense game!</p>
                    
                    <div class='score-display'>
                        <h3>Your Achievement:</h3>
                        <p><strong>Score:</strong> " . number_format($score) . " points</p>
                        <p><strong>Wave Reached:</strong> Wave " . $wave . "</p>
                    </div>
                    
                    <p>Keep up the great work! Continue playing to improve your skills and climb the leaderboard.</p>
                    
                    <a href='http://localhost/Websys/public/game.php' class='button'>Play Again!</a>
                    <a href='http://localhost/Websys/public/dashboard.php' class='button'>View Dashboard</a>
                    
                    <p>Challenge yourself to reach even higher scores and unlock new tower types!</p>
                </div>
                <div class='footer'>
                    <p>Aprender - Gamified Problem-Based Learning System</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getContactFormEmailBody($name, $email, $subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .message-box { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #667eea; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .info-row { margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 Contact Form Submission</h1>
                    <p>Aprender Support</p>
                </div>
                <div class='content'>
                    <h2>Contact Form Details</h2>
                    
                    <div class='info-row'>
                        <strong>Name:</strong> " . htmlspecialchars($name) . "
                    </div>
                    <div class='info-row'>
                        <strong>Email:</strong> " . htmlspecialchars($email) . "
                    </div>
                    <div class='info-row'>
                        <strong>Subject:</strong> " . htmlspecialchars($subject) . "
                    </div>
                    
                    <h3>Message:</h3>
                    <div class='message-box'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                    
                    <p><strong>Reply directly to this email to respond to the user.</strong></p>
                    
                    <p>Submitted on: " . date('Y-m-d H:i:s') . "</p>
                </div>
                <div class='footer'>
                    <p>Aprender - Gamified Problem-Based Learning System</p>
                    <p>This message was sent from the contact form on the website.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
