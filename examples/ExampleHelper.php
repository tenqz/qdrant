<?php

declare(strict_types=1);

/**
 * Helper class for examples
 * Provides utility methods for console output formatting
 */
class ExampleHelper
{
    /**
     * Print section header
     *
     * @param string $title Section title
     * @return void
     */
    public static function section($title)
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "  {$title}\n";
        echo str_repeat('=', 70) . "\n\n";
    }
    
    /**
     * Print success message
     *
     * @param string $message Success message
     * @return void
     */
    public static function success($message)
    {
        echo "✅ {$message}\n";
    }
    
    /**
     * Print info message
     *
     * @param string $message Info message
     * @return void
     */
    public static function info($message)
    {
        echo "ℹ️  {$message}\n";
    }
    
    /**
     * Print warning message
     *
     * @param string $message Warning message
     * @return void
     */
    public static function warning($message)
    {
        echo "⚠️  {$message}\n";
    }
    
    /**
     * Print error message
     *
     * @param string $message Error message
     * @return void
     */
    public static function error($message)
    {
        echo "❌ {$message}\n";
    }
    
    /**
     * Print horizontal separator
     *
     * @param string $char Character for separator
     * @param int $length Length of separator
     * @return void
     */
    public static function separator($char = '━', $length = 70)
    {
        echo str_repeat($char, $length) . "\n";
    }
}

