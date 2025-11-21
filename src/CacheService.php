<?php

/**
 * Simple file-based caching service
 * For production, consider using Redis or Memcached
 */
class CacheService {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../cache';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cache = unserialize($data);
        
        // Check if expired
        if ($cache['expires'] < time()) {
            unlink($filename);
            return null;
        }
        
        return $cache['value'];
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $cache = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($filename, serialize($cache));
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Remember: Get from cache or execute callback and cache result
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache filename for key
     */
    private function getCacheFilename($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '/*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = file_get_contents($file);
                $cache = unserialize($data);
                
                if ($cache['expires'] < time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}
