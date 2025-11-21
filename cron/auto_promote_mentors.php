<?php
/**
 * Auto-Promote Eligible Students to Mentor
 * 
 * This script should be run periodically via cron (e.g., daily)
 * Example cron: 0 2 * * * php /path/to/auto_promote_mentors.php
 */

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/MentorPromotion.php';

// Log start
$logFile = __DIR__ . '/../logs/mentor_promotions.log';
$timestamp = date('Y-m-d H:i:s');

function logMessage($message, $logFile, $timestamp) {
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    logMessage("Starting auto-promotion check...", $logFile, $timestamp);
    
    $mentorPromotion = new MentorPromotion(db());
    
    // Run auto-promotion
    $promoted = $mentorPromotion->autoPromoteEligibleStudents();
    
    if (empty($promoted)) {
        logMessage("No eligible students found for promotion.", $logFile, $timestamp);
    } else {
        logMessage("Successfully promoted " . count($promoted) . " student(s) to mentor:", $logFile, $timestamp);
        foreach ($promoted as $userId) {
            logMessage("  - User ID: {$userId}", $logFile, $timestamp);
        }
    }
    
    logMessage("Auto-promotion check completed successfully.", $logFile, $timestamp);
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage(), $logFile, $timestamp);
    logMessage("Stack trace: " . $e->getTraceAsString(), $logFile, $timestamp);
    exit(1);
}

exit(0);
