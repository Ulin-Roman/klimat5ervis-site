<?php
// Proposal only; not deployed. This is project code, not a HostCMS cache driver.
if (PHP_SAPI !== 'cli' && !defined('HOSTCMS')) { http_response_code(404); exit; }

final class KlimatFacetOptionsCache20261117
{
    public const DATA_GUARD = "<?php http_response_code(404); exit; __halt_compiler();\n";
    public const MAX_FILE_BYTES = 131072;
    private const IDS = array(22, 28, 39);
    private $directory, $ttl, $clock;

    // Provision this exclusive directory beforehand; front-end requests never mkdir/chmod it.
    public function __construct(string $directory, int $ttl = 300, ?callable $clock = null)
    {
        if ($ttl < 1 || $ttl > 300) throw new InvalidArgumentException('TTL must be 1..300 seconds');
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->ttl = $ttl;
        $this->clock = $clock ?? static function () { return time(); };
    }

    // Only trusted server-side metadata belongs here, never request/filter/cart values.
    public static function key(int $siteId, int $shopId, string $generation, array $definitions): string
    {
        if ($siteId !== 2 || $shopId !== 1 || !preg_match('/^[a-zA-Z0-9_.-]{1,48}$/D', $generation)) {
            throw new InvalidArgumentException('Unexpected facet cache scope');
        }
        $normalized = array();
        foreach ($definitions as $definition) {
            if (!is_array($definition) || array_diff(array_keys($definition), array('id', 'name', 'type', 'list_id'))
                || !is_int($definition['id'] ?? null) || !in_array($definition['id'], self::IDS, true)
                || !is_string($definition['name'] ?? null) || strlen($definition['name']) > 320
                || ($definition['type'] ?? null) !== 1 || !is_int($definition['list_id'] ?? null)
                || isset($normalized[$definition['id']])) throw new InvalidArgumentException('Unexpected facet definition');
            $normalized[$definition['id']] = array('id' => $definition['id'], 'name' => $definition['name'], 'type' => 1, 'list_id' => $definition['list_id']);
        }
        ksort($normalized, SORT_NUMERIC);
        if (array_keys($normalized) !== self::IDS) throw new InvalidArgumentException('Three allowlisted definitions required');
        return hash('sha256', json_encode(array('schema' => 1, 'site' => $siteId, 'shop' => $shopId,
            'generation' => $generation, 'definitions' => array_values($normalized)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function remember(string $key, callable $build): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) throw new InvalidArgumentException('Invalid cache key');
        $hit = $this->quiet(function () use ($key) { return $this->read($key); });
        if ($hit !== null) return $hit; // Empty option arrays are valid hits.
        $lock = $this->quiet(function () { return $this->lock(); });
        if (!is_resource($lock)) return $build();
        try {
            $hit = $this->quiet(function () use ($key) { return $this->read($key); });
            if ($hit !== null) return $hit;
            // Do not catch native query failures or run the builder twice on failure.
            $fresh = $build();
            if ($this->validValues($fresh)) {
                $this->quiet(function () use ($key, $fresh) { $this->write($key, $fresh); });
            }
            return $fresh;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    // Explicit admin/deployment operation; no public HTTP clear parameter or route is supplied.
    public function invalidate(): bool
    {
        $lock = $this->quiet(function () { return $this->lock(); });
        if (!is_resource($lock)) return false;
        try {
            return (bool) $this->quiet(function () {
                $file = $this->directory . '/facet-options.php';
                if (is_link($file)) return false;
                return !file_exists($file) || (is_file($file) && unlink($file));
            }, false);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private function quiet(callable $operation, $fallback = null)
    {
        set_error_handler(static function () { return true; }, E_WARNING | E_NOTICE);
        try { return $operation(); }
        catch (Throwable $ignored) { return $fallback; }
        finally { restore_error_handler(); }
    }

    private function available(): bool
    {
        clearstatcache(true, $this->directory);
        $real = realpath($this->directory);
        return $real !== false && $real === $this->directory && is_dir($real) && !is_link($real)
            && (fileperms($real) & 0022) === 0;
    }

    private function validValues($values): bool
    {
        if (!is_array($values) || count($values) !== 3 || !function_exists('mb_strlen')) return false;
        foreach ($values as $id => $options) {
            if (!in_array($id, self::IDS, true) || !is_array($options) || count($options) > 200) return false;
            if ($options && array_keys($options) !== range(0, count($options) - 1)) return false;
            foreach ($options as $value) {
                if (!is_string($value) || $value === '' || strlen($value) > 320
                    || preg_match('//u', $value) !== 1 || mb_strlen($value, 'UTF-8') > 80) return false;
            }
        }
        return true;
    }

    private function read(string $key): ?array
    {
        if (!$this->available()) return null;
        $file = $this->directory . '/facet-options.php';
        clearstatcache(true, $file);
        if (is_link($file) || !is_file($file)) return null;
        $raw = file_get_contents($file, false, null, 0, self::MAX_FILE_BYTES + 1);
        $guardLength = strlen(self::DATA_GUARD);
        if (!is_string($raw) || strlen($raw) > self::MAX_FILE_BYTES || strncmp($raw, self::DATA_GUARD, $guardLength) !== 0) return null;
        $data = json_decode(substr($raw, $guardLength), true, 16, JSON_THROW_ON_ERROR);
        $now = (int) call_user_func($this->clock);
        if (!is_array($data) || count($data) !== 5 || ($data['schema'] ?? null) !== 1 || ($data['key'] ?? null) !== $key
            || !is_int($data['created_at'] ?? null) || !is_int($data['expires_at'] ?? null)
            || $data['created_at'] < 0 || $now < $data['created_at'] || $now >= $data['expires_at']
            || $data['expires_at'] <= $data['created_at'] || $data['expires_at'] - $data['created_at'] > $this->ttl
            || !$this->validValues($data['values'] ?? null)) return null;
        return $data['values'];
    }

    private function lock()
    {
        if (!$this->available() || !is_writable($this->directory)) return null;
        $file = $this->directory . '/facet-options.lock.php';
        if (is_link($file) || (file_exists($file) && !is_file($file))) return null;
        $handle = fopen($file, 'c+b');
        if (!is_resource($handle)) return null;
        if (!flock($handle, LOCK_EX | LOCK_NB)) { fclose($handle); return null; }
        $ok = false;
        try {
            $size = fstat($handle)['size'];
            if ($size === 0) {
                if (fwrite($handle, self::DATA_GUARD) !== strlen(self::DATA_GUARD) || !fflush($handle)) return null;
            } elseif ($size !== strlen(self::DATA_GUARD) || fread($handle, $size) !== self::DATA_GUARD) return null;
            $ok = true;
            return $handle;
        } finally { if (!$ok) { flock($handle, LOCK_UN); fclose($handle); } }
    }

    private function write(string $key, array $values): void
    {
        $now = (int) call_user_func($this->clock);
        $json = json_encode(array('schema' => 1, 'key' => $key, 'created_at' => $now,
            'expires_at' => $now + $this->ttl, 'values' => $values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen(self::DATA_GUARD) + strlen($json) > self::MAX_FILE_BYTES) return;
        $file = $this->directory . '/facet-options.php';
        $temp = $this->directory . '/facet-options.write.php';
        if (!$this->available() || is_link($file) || (file_exists($file) && !is_file($file)) || is_link($temp)) return;
        // One fixed reserved temp under the mutex bounds leftovers after repeated process crashes.
        if (file_exists($temp) && (!is_file($temp) || !unlink($temp))) return;
        $handle = fopen($temp, 'x+b');
        if (!is_resource($handle)) return;
        try {
            if (fwrite($handle, self::DATA_GUARD) !== strlen(self::DATA_GUARD) || !fflush($handle)) return;
            if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) return;
            fclose($handle); $handle = null;
            rename($temp, $file); // Same-directory replacement: readers see a complete old or new file.
        } finally {
            if (is_resource($handle)) fclose($handle);
            if (is_file($temp) && !is_link($temp)) unlink($temp);
        }
    }
}
