<?php 
require_once(ABSPATH . 'wp-admin/includes/file.php');

class SPROFileCacheHandler {
    private $baseCacheDir;
    private $wp_filesystem;
    private $group_folders = array(); // Add an initialized flag per group

    public function __construct() {
        // Hook the filesystem initialization to an appropriate action.
        add_action('plugins_loaded', array($this, 'initializeFileSystem'));
        // Define the base cache directory within wp-content/cache
        $this->baseCacheDir = WP_CONTENT_DIR . '/cache/scalability-pro/';
    }

    public function initializeFileSystem() {
        global $wp_filesystem;

        if ( ! $wp_filesystem || !($wp_filesystem instanceof WP_Filesystem_Base)) {
            $url = wp_nonce_url('admin.php?page=my-plugin-page', 'my-plugin-action');
            $creds = request_filesystem_credentials($url, '', false, false, null);

            // Check if credentials are provided or the form needs to be displayed
            if (false === $creds) {
                return false;
            }

            if (!WP_Filesystem($creds)) {
                // Unable to initialize the WordPress filesystem API
                return false;
            }
        }

        $this->wp_filesystem = $wp_filesystem;
        return true;
    }

    private function ensureGroupDirExists($group) {
        // Check if we've already verified/created this group's folder
        if (isset($this->group_folders[$group])) {
            return $this->group_folders[$group];
        }

        // Directory checks and creation
        $directories = [WP_CONTENT_DIR . '/cache/', $this->baseCacheDir, $this->baseCacheDir . trailingslashit($group)];
        foreach ($directories as $dir) {
            if (!$this->wp_filesystem->is_dir($dir) && !$this->wp_filesystem->mkdir($dir, FS_CHMOD_DIR)) {
                $this->group_folders[$group] = false;
                return false;
            }
        }

        $this->group_folders[$group] = $this->baseCacheDir . trailingslashit($group);
        return $this->group_folders[$group];
    }
    

    public function setCache($key, $data, $group, $expiration) {
        $groupDir = $this->ensureGroupDirExists($group);
        
        // Check if the group directory was successfully created
        if ($groupDir === false) {
            return false; // Failed to ensure the group directory exists
        }
        $expiryTimestamp = time() + $expiration; // Expiry timestamp
        $expiryDate = date('Y-m-d H:i:s', $expiryTimestamp); // Format as a date string

        $filePath = $groupDir . md5($key) . '.cache';
        $cacheData = json_encode(['expiry' => $expiryDate, 'data' => $data]);
        
        // Attempt to write the cache data to the file
        $writeSuccess = $this->wp_filesystem->put_contents($filePath, $cacheData, FS_CHMOD_FILE);
        
        // Return true if the write operation was successful, false otherwise
        return $writeSuccess !== false;
    }
    
    public function getCache($key, $group) {
        $groupDir = $this->ensureGroupDirExists($group);
        $filePath = $groupDir . md5($key) . '.cache';
        if ($this->wp_filesystem->exists($filePath)) {
            $contents = $this->wp_filesystem->get_contents($filePath);
            $cacheData = json_decode($contents, true);
            if (time() < $cacheData['expiry']) {
                return $cacheData['data'];
            } else {
                // Cache expired, delete the file
                $this->wp_filesystem->delete($filePath);
                return false;
            }
        }
        return false;
    }

    public function deleteCache($key, $group) {
        $groupDir = $this->ensureGroupDirExists($group);
        // Delete a specific cache file
        $filePath = $groupDir . md5($key) . '.cache';
        if ($this->wp_filesystem->exists($filePath)) {
            $this->wp_filesystem->delete($filePath);
        }
    }
    public function deleteCacheGroup($group) {
        $groupDir = $this->ensureGroupDirExists($group);
        // Wipe the entire group cache directory
        $cacheFiles = $this->wp_filesystem->dirlist($groupDir);
        foreach ($cacheFiles as $file) {
            if ('f' === $file['type']) { // Ensure it's a file
                $this->wp_filesystem->delete($groupDir . $file['name']);
            }
        }
    }
    public function isFileCachingPossible() {
        if (!$this->initializeFileSystem()) {
            return false; // Cannot initialize filesystem, hence caching is not possible
        }
        // Check if the test has already been performed and result saved
        $cachedCapability = get_option('spro_file_cache_possible');
        if ($cachedCapability !== false) {
            // Return the stored test result (true or false)
            return $cachedCapability === '1';
        }
    
        // Use a unique key for the test to avoid conflicts
        $testKey = 'test_cache_capability_check';
        $testGroup = 'test';
        $testData = 'This is a test.';
    
        // Attempt to use setCache to write a test cache file
        if ($this->setCache($testKey, $testGroup, $testData, 0)) {
            // Successfully wrote the test cache, indicate caching is possible
            add_option('spro_file_cache_possible', '1', '', 'yes');
            // Cleanup: Delete the test cache file
            $this->deleteCache($testKey, $testGroup);
            return true;
        } else {
            // Failed to write the test cache, indicate caching is not possible
            add_option('spro_file_cache_possible', '0', '', 'yes');
            return false;
        }
    }
    


}
$SPRO_FILE_CACHE_HANDLER = new SPROFileCacheHandler();