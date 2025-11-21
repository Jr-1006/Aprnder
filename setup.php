<?php
/**
 * Database Setup Script
 * Run this once to set up the database and create initial data
 */

// Include configuration
require_once __DIR__ . '/src/config.php';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/db/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }

    echo "✅ Database setup completed successfully!\n";
    echo "📊 Database: " . DB_NAME . "\n";
    echo "👤 Admin user: admin@example.com / Admin123!\n";
    echo "🌐 You can now access the application at: http://localhost/Websys/\n";

} catch (Exception $e) {
    echo "❌ Error setting up database: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in src/config.php\n";
}
?>
