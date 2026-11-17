<?php
// Dedicated HostCMS page: execute only, no public template wrapping. PHP 7.4+.
if (!headers_sent()) {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Content-Type-Options: nosniff');
}
if (!class_exists('Core_Auth') || !Core_Auth::logged()) { http_response_code(403); echo 'Доступ закрыт'; return; }
$kibUser = Core_Auth::getCurrentUser();
if (!$kibUser || (int) $kibUser->superuser !== 1 || (int) $kibUser->read_only !== 0 || (int) $kibUser->dismissed !== 0) {
    http_response_code(403); echo 'Доступ закрыт'; return;
}
if (session_status() !== PHP_SESSION_ACTIVE && class_exists('Core_Session')) Core_Session::start();
if (session_status() !== PHP_SESSION_ACTIVE) { http_response_code(503); echo 'Сессия недоступна'; return; }
if (empty($_SESSION['klimat_image_build_20261117_csrf'])) $_SESSION['klimat_image_build_20261117_csrf'] = bin2hex(random_bytes(32));
$kibToken = $_SESSION['klimat_image_build_20261117_csrf'];
$kibPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if ($kibPost && (!is_string($_POST['csrf'] ?? null) || !hash_equals($kibToken, $_POST['csrf']))) {
    if (method_exists('Core_Session', 'close')) Core_Session::close(); else session_write_close();
    http_response_code(403); echo 'CSRF'; return;
}
if (method_exists('Core_Session', 'close')) Core_Session::close(); else session_write_close();

class KlimatImageBuild20261117
{
    private $root, $private, $out, $lock, $state, $bytes = 0, $start, $cpuStart, $reserve, $active = false, $ioCategory = null, $accountingFullScan = false;
    private const OUT = '/assets/catalog-images-20261117';
    private const MAX_BYTES = 629145600;
    private const ACCOUNTING_VERSION = 1;
    private const RECIPE = 'webp-v1-q82-exif-360-720-1280';
    private const DATA_GUARD = "<?php http_response_code(404); exit; __halt_compiler();\n";
    private const HELPER_GUARD = "<?php\nif (isset(\$_SERVER['SCRIPT_FILENAME']) && realpath(\$_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }\n";
    public function __construct()
    {
        $this->root = realpath(defined('CMS_FOLDER') ? CMS_FOLDER : ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if (!$this->root || $this->root === DIRECTORY_SEPARATOR) throw new RuntimeException('root_unavailable');
        $this->private = $this->root . '/hostcmsfiles/.klimat-image-build-20261117';
        $this->out = $this->root . self::OUT;
        $this->start = microtime(true); $this->cpuStart = $this->cpu();
    }
    private function cpu()
    {
        if (!function_exists('getrusage')) return microtime(true);
        $r = getrusage();
        return $r['ru_utime.tv_sec'] + $r['ru_utime.tv_usec'] / 1000000 + $r['ru_stime.tv_sec'] + $r['ru_stime.tv_usec'] / 1000000;
    }
    private function limit()
    {
        $v = trim(ini_get('memory_limit')); if ($v === '-1') return 268435456;
        $unit = strtolower(substr($v, -1)); $n = (float) $v;
        if ($unit === 'g') $n *= 1073741824; elseif ($unit === 'm') $n *= 1048576; elseif ($unit === 'k') $n *= 1024;
        return min(268435456, $n);
    }
    private function mkdirSafe($path, $mode)
    {
        clearstatcache(true, $path);
        if (@is_link($path)) throw new RuntimeException('symlink_rejected');
        if (!@is_dir($path)) {
            $message = '';
            if (!$this->captureIo(static function () use ($path, $mode) { return mkdir($path, $mode); }, $message)) {
                $this->ioCategory = $this->classifyIo($message);
                throw new RuntimeException('directory_create_failed');
            }
        }
        clearstatcache(true, $path);
        if (@realpath($path) !== $path) throw new RuntimeException('directory_canonical_mismatch');
    }
    private function read($name)
    {
        $file = $this->private . '/' . $name;
        if (is_link($file)) throw new RuntimeException('symlink_rejected');
        if (!is_file($file)) return null;
        if (filesize($file) > 67108864 || memory_get_usage(true) + filesize($file) * 8 > $this->limit() - 8388608) throw new RuntimeException('state_memory_limit');
        $raw = @file_get_contents($file);
        if ($raw === false || strncmp($raw, self::DATA_GUARD, strlen(self::DATA_GUARD)) !== 0) throw new RuntimeException('invalid_state_guard');
        $data = json_decode(substr($raw, strlen(self::DATA_GUARD)), true);
        if (!is_array($data)) throw new RuntimeException('invalid_state');
        return $data;
    }
    private function atomic($name, $value, $raw = false)
    {
        $path = $this->private . '/' . $name;
        if (!preg_match('/^[a-z0-9-]+\.php$/D', $name)) throw new RuntimeException('invalid_private_filename');
        if (is_link($path)) throw new RuntimeException('symlink_rejected');
        $guard = $raw ? self::HELPER_GUARD : self::DATA_GUARD;
        if ($raw && ($name !== 'images-render.php' || strncmp($value, $guard, strlen($guard)) !== 0)) throw new RuntimeException('invalid_helper_guard');
        $data = $raw ? substr($value, strlen($guard)) : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $tmp = $this->private . '/.write-' . bin2hex(random_bytes(16)) . '.php';
        $message = ''; $handle = $this->captureIo(static function () use ($tmp) { return fopen($tmp, 'x+b'); }, $message);
        if (!is_resource($handle)) { $this->ioCategory = $this->classifyIo($message); throw new RuntimeException('write_failed'); }
        try {
            @chmod($tmp, 0600);
            // No payload byte is written before the complete executable guard is on disk.
            if (fwrite($handle, $guard) !== strlen($guard) || !fflush($handle)) throw new RuntimeException('write_failed');
            if (fwrite($handle, $data) !== strlen($data) || !fflush($handle)) throw new RuntimeException('write_failed');
            fclose($handle); $handle = null;
            if (!@rename($tmp, $path)) throw new RuntimeException('write_failed');
        } finally { if (is_resource($handle)) fclose($handle); if (is_file($tmp)) @unlink($tmp); }
    }
    private function path($url)
    {
        if (!preg_match('~^/upload/shop_1(?:/[A-Za-z0-9_.-]+)*$~D', $url)) return false;
        $path = $this->root;
        foreach (explode('/', ltrim($url, '/')) as $part) {
            if ($part === '.' || $part === '..') return false;
            $path .= '/' . $part; if (is_link($path)) return false;
        }
        return realpath($path) === $path ? $path : false;
    }
    private function ready()
    {
        $parent = dirname($this->private);
        if (@is_link($parent) || @realpath($parent) !== $parent || !@is_dir($parent)) throw new RuntimeException('storage_parent_unavailable');
        $this->mkdirSafe($this->private, 0700);
        $denyFile = $this->private . '/.htaccess';
        $deny = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
        if (is_link($denyFile) || (is_file($denyFile) && @file_get_contents($denyFile) !== $deny)) throw new RuntimeException('private_config_conflict');
        if (!is_file($denyFile)) {
            $message = ''; $denyHandle = $this->captureIo(static function () use ($denyFile) { return fopen($denyFile, 'x+b'); }, $message);
            if (!is_resource($denyHandle)) { $this->ioCategory = $this->classifyIo($message); throw new RuntimeException('private_config_failed'); }
            try { @chmod($denyFile, 0600); if (fwrite($denyHandle, $deny) !== strlen($deny) || !fflush($denyHandle)) throw new RuntimeException('private_config_failed'); }
            finally { fclose($denyHandle); }
        }
        if (is_link($this->private . '/worker.lock.php')) throw new RuntimeException('symlink_rejected');
        $message = '';
        $this->lock = $this->captureIo(function () { return fopen($this->private . '/worker.lock.php', 'c'); }, $message);
        if (!is_resource($this->lock)) {
            $this->ioCategory = $this->classifyIo($message);
            throw new RuntimeException('lock_open_failed');
        }
        $message = ''; $wouldBlock = 0;
        $locked = $this->captureIo(function () use (&$wouldBlock) { return flock($this->lock, LOCK_EX | LOCK_NB, $wouldBlock); }, $message);
        if (!$locked) {
            $this->ioCategory = $wouldBlock ? 'contention' : $this->classifyIo($message);
            @fclose($this->lock); $this->lock = null;
            throw new RuntimeException($wouldBlock ? 'lock_busy' : 'lock_failed');
        }
        @chmod($this->private . '/worker.lock.php', 0600);
        if (fstat($this->lock)['size'] === 0 && (fwrite($this->lock, self::DATA_GUARD) !== strlen(self::DATA_GUARD) || !fflush($this->lock))) throw new RuntimeException('lock_guard_failed');
        $this->state = $this->read('state.php');
        if ($this->state && ($this->state['root_hash'] ?? '') !== hash('sha256', $this->root)) throw new RuntimeException('root_mismatch');
    }
    private function captureIo(callable $operation, &$message)
    {
        $message = ''; error_clear_last();
        set_error_handler(static function ($severity, $text) use (&$message) {
            if ($message === '') $message = (string) $text;
            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
        try {
            return $operation();
        } catch (Throwable $e) {
            if ($message === '') $message = $e->getMessage();
            return false;
        } finally {
            restore_error_handler();
        }
    }
    private function classifyIo($message)
    {
        foreach (array('open_basedir' => 'open_basedir', 'permission denied' => 'permission_denied',
            'operation not permitted' => 'operation_not_permitted', 'read-only file system' => 'read_only_filesystem',
            'quota' => 'quota_exceeded', 'no space left' => 'no_space', 'too many open files' => 'file_descriptor_limit',
            'disabled' => 'disabled_function', 'undefined function' => 'disabled_function',
            'no such file' => 'missing_directory', 'is a directory' => 'path_is_directory',
            'not supported' => 'unsupported_operation', 'invalid argument' => 'invalid_argument') as $needle => $category) {
            if (stripos((string) $message, $needle) !== false) return $category;
        }
        return 'unknown';
    }
    public function ioCategory() { return $this->ioCategory; }
    public function probe()
    {
        $gd = function_exists('gd_info') ? gd_info() : array();
        $webp = !empty($gd['WebP Support']) && function_exists('imagewebp') && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng');
        $storage = array('open_basedir_configured' => trim((string) ini_get('open_basedir')) !== '');
        foreach (array('private_parent' => dirname($this->private), 'private' => $this->private,
            'output_parent' => $this->root . '/assets', 'output' => $this->out) as $label => $path) {
            clearstatcache(true, $path);
            $storage[$label] = array('exists' => @file_exists($path), 'is_directory' => @is_dir($path),
                'writable' => @is_writable($path), 'is_symlink' => @is_link($path), 'canonical_match' => @realpath($path) === $path);
        }
        $storage['cms_root_matches_document_root'] = @realpath($_SERVER['DOCUMENT_ROOT'] ?? '') === $this->root;
        $disabled = array_fill_keys(array_filter(array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))))), true);
        $storage['functions_enabled'] = array();
        foreach (array('fopen', 'flock', 'fclose', 'mkdir', 'rename', 'file_put_contents', 'tempnam', 'chmod', 'disk_free_space') as $function) {
            $storage['functions_enabled'][$function] = function_exists($function) && !isset($disabled[$function]);
        }
        return array('php' => PHP_VERSION, 'gd_webp' => $webp, 'exif' => function_exists('exif_read_data'),
            'dom' => class_exists('DOMDocument'), 'opcache' => function_exists('opcache_get_status') && (bool) @opcache_get_status(false),
            'memory_cap_mb' => (int) ($this->limit() / 1048576), 'cpu_clock' => function_exists('getrusage') ? 'cpu' : 'wall_fallback', 'storage' => $storage);
    }
    private function budget($forceScan = false)
    {
        // Only a successfully committed batch can supply a trusted counter.
        $trusted = !$forceScan && ($this->state['output_accounting_version'] ?? null) === self::ACCOUNTING_VERSION
            && ($this->state['output_dirty'] ?? true) === false
            && is_int($this->state['output_bytes'] ?? null) && $this->state['output_bytes'] >= 0;
        if ($trusted) {
            $this->bytes = $this->state['output_bytes'];
        } else {
            $this->accountingFullScan = true;
            $this->bytes = 0;
            // Include orphan finals, partial temps and .htaccess, not just mapped WebP files.
            foreach (new DirectoryIterator($this->out) as $entry) {
                if ($entry->isDot()) continue;
                if ($entry->isLink() || !$entry->isFile()) throw new RuntimeException('output_directory_conflict');
                $this->bytes += $entry->getSize();
            }
        }
        return $this->bytes < self::MAX_BYTES && @disk_free_space($this->out) > 134217728;
    }
    private function markOutputDirty()
    {
        $this->state['output_accounting_version'] = self::ACCOUNTING_VERSION;
        $this->state['output_dirty'] = true;
        // Must finish before any public output temp or final can be created.
        $this->save();
    }
    private function save($clean = false)
    {
        if ($clean) {
            $this->state['output_accounting_version'] = self::ACCOUNTING_VERSION;
            $this->state['output_dirty'] = false;
        }
        $this->state['output_bytes'] = $this->bytes;
        $this->state['updated'] = time();
        try { $this->atomic('state.php', $this->state); }
        catch (Throwable $e) {
            if ($clean) $this->state['output_dirty'] = true;
            throw $e;
        }
    }
    private function removeOutputTemp($path)
    {
        clearstatcache(true, $path);
        if (is_link($path) || (file_exists($path) && (!is_file($path) || !@unlink($path)))) throw new RuntimeException('output_cleanup_failed');
        clearstatcache(true, $path);
        if (file_exists($path) || is_link($path)) throw new RuntimeException('output_cleanup_failed');
    }
    private function finishCpu()
    {
        $this->state['cpu_seconds'] += max(0, $this->cpu() - $this->cpuStart);
        $this->cpuStart = $this->cpu();
    }
    private function expired()
    {
        return microtime(true) - $this->start >= 4 || $this->cpu() - $this->cpuStart >= 2
            || $this->state['cpu_seconds'] + $this->cpu() - $this->cpuStart >= 2400;
    }
    private function countReason($reason)
    {
        $this->state['reasons'][$reason] = ($this->state['reasons'][$reason] ?? 0) + 1;
    }
    private function scan()
    {
        $s =& $this->state;
        while ($s['dir_cursor'] < count($s['dirs']) && !$this->expired()) {
            $url = $s['dirs'][$s['dir_cursor']]; $path = $this->path($url);
            if (!$path || !is_dir($path)) { $s['dir_cursor']++; $this->countReason('scan_directory_skipped'); continue; }
            $entries = @scandir($path); if ($entries === false) throw new RuntimeException('scan_failed');
            foreach ($entries as $name) {
                if ($name === '.' || $name === '..' || !preg_match('~^[A-Za-z0-9_.-]+$~D', $name)) continue;
                $full = $path . '/' . $name; if (is_link($full)) continue;
                if (is_dir($full)) { $s['dirs'][] = $url . '/' . $name; continue; }
                $file = $url . '/' . $name;
                if (!is_file($full) || !preg_match('~/(item_([0-9]+))/(small_)?(item_image([0-9]+)|shop_property_file_[0-9]+_[0-9]+)\.(jpe?g|png)$~iD', $file, $m)) continue;
                if ($m[5] !== '' && $m[5] !== $m[2]) continue;
                $key = $url . '/' . $m[4] . '.' . strtolower($m[6]);
                if (!isset($s['groups'][$key])) $s['groups'][$key] = array('id' => (int) $m[2], 'full' => null, 'small' => null);
                $s['groups'][$key][$m[3] === '' ? 'full' : 'small'] = $file;
            }
            $s['dir_cursor']++;
            if (memory_get_usage(true) > $this->limit() * .65) throw new RuntimeException('scan_memory_limit');
        }
        if ($s['dir_cursor'] === count($s['dirs'])) {
            $groups = array_values($s['groups']);
            // Native full/small usually share an extension; also pair a lone full file across jpg/png.
            $fullByStem = array();
            foreach ($groups as $i => $g) if ($g['full']) $fullByStem[preg_replace('~\.[^.]+$~', '', $g['full'])][] = $i;
            foreach ($groups as $i => $g) if (!$g['full'] && $g['small']) {
                $stem = preg_replace('~\.[^.]+$~', '', str_replace('/small_', '/', $g['small']));
                if (isset($fullByStem[$stem]) && count($fullByStem[$stem]) === 1) {
                    $j = $fullByStem[$stem][0];
                    if (!$groups[$j]['small']) { $groups[$j]['small'] = $g['small']; unset($groups[$i]); }
                    else $groups[$i]['full'] = $groups[$j]['full'];
                }
            }
            $groups = array_values($groups); unset($fullByStem);
            $priority = array_flip(array(7715, 7972, 8375, 7195, 7531, 8103, 8104, 9356));
            usort($groups, static function ($a, $b) use ($priority) { return ($priority[$a['id']] ?? 100) <=> ($priority[$b['id']] ?? 100); });
            $this->atomic('queue.php', $groups); $s['total'] = count($groups); $s['phase'] = 'build';
            unset($s['dirs'], $s['groups'], $s['dir_cursor']);
        }
    }
    private function isApng($path)
    {
        $f = @fopen($path, 'rb'); if (!$f) return true;
        try {
            if (fread($f, 8) !== "\x89PNG\r\n\x1a\n") return true;
            for ($n = 0; $n < 100000; $n++) {
                $header = fread($f, 8); if (strlen($header) !== 8) return true;
                $length = unpack('Nsize', substr($header, 0, 4))['size']; $type = substr($header, 4, 4);
                if ($type === 'acTL') return true;
                if ($type === 'IEND') return false;
                if ($length > 16777216 || fseek($f, $length + 4, SEEK_CUR) !== 0) return true;
            }
            return true;
        } finally { fclose($f); }
    }
    private function validWebp($path, $w, $h)
    {
        $n = @filesize($path); if (!$n || is_link($path)) return false;
        $head = @file_get_contents($path, false, null, 0, 12); $info = @getimagesize($path);
        return strlen((string) $head) === 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP'
            && $info && $info[0] === $w && $info[1] === $h && ($info['mime'] ?? '') === 'image/webp';
    }
    private function orient($image, $orientation)
    {
        if (in_array($orientation, array(2, 4, 5, 7), true) && !imageflip($image, IMG_FLIP_HORIZONTAL)) throw new RuntimeException('orientation_failed');
        $angle = array(3 => 180, 4 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90)[$orientation] ?? 0;
        if ($angle) {
            $next = imagerotate($image, $angle, 0); if (!$next) throw new RuntimeException('orientation_failed');
            imagedestroy($image); return $next;
        }
        return $image;
    }
    private function convert($group)
    {
        $url = $group['full'] ?: $group['small']; $path = $this->path($url);
        if (!$path || !is_file($path)) throw new RuntimeException('source_missing');
        clearstatcache(true, $path); $size = filesize($path); $mtime = filemtime($path);
        if (!$size || $size > 16777216) throw new RuntimeException('source_size');
        $info = @getimagesize($path);
        if (!$info || !in_array($info['mime'] ?? '', array('image/jpeg', 'image/png'), true)) throw new RuntimeException('source_format');
        $w = $info[0]; $h = $info[1]; $pixels = $w * $h;
        if (!$w || !$h || $pixels > 14000000) throw new RuntimeException('source_pixels');
        if (memory_get_usage(true) + $pixels * 12 + 1280 * 1280 * 8 + 25165824 > $this->limit()) throw new RuntimeException('source_memory');
        if ($info['mime'] === 'image/png' && $this->isApng($path)) throw new RuntimeException('animated_or_invalid_png');
        $orientation = 1;
        if ($info['mime'] === 'image/jpeg') {
            if (!function_exists('exif_read_data')) throw new RuntimeException('exif_unavailable');
            $exif = @exif_read_data($path); $orientation = (int) ($exif['Orientation'] ?? 1); unset($exif);
        }
        $source = null; $dest = null; $tmp = null; $variants = array();
        try {
            $source = $info['mime'] === 'image/jpeg' ? @imagecreatefromjpeg($path) : @imagecreatefrompng($path);
            if (!$source) throw new RuntimeException('decode_failed');
            $source = $this->orient($source, $orientation); $w = imagesx($source); $h = imagesy($source);
            foreach (array(360, 720, 1280) as $bound) {
                // Bound both sides: tall pictures must not allocate an unbounded destination bitmap.
                $ratio = min(1, $bound / max($w, $h)); $dw = max(1, (int) round($w * $ratio)); $dh = max(1, (int) round($h * $ratio));
                if (isset($variants[$dw])) continue;
                $name = hash('sha256', self::RECIPE . '|' . $url . '|' . $mtime . '|' . $size . '|' . $dw . 'x' . $dh) . '.webp';
                $output = $this->out . '/' . $name;
                if (is_link($output)) throw new RuntimeException('symlink_rejected');
                if (is_file($output)) {
                    if (!$this->validWebp($output, $dw, $dh)) throw new RuntimeException('output_conflict');
                } else {
                    if ($this->bytes + 16777216 > self::MAX_BYTES || @disk_free_space($this->out) < 150994944) throw new RuntimeException('output_budget');
                    $dest = imagecreatetruecolor($dw, $dh); if (!$dest) throw new RuntimeException('resize_failed');
                    imagealphablending($dest, false); imagesavealpha($dest, true);
                    imagefilledrectangle($dest, 0, 0, $dw - 1, $dh - 1, imagecolorallocatealpha($dest, 0, 0, 0, 127));
                    if (!imagecopyresampled($dest, $source, 0, 0, 0, 0, $dw, $dh, $w, $h)) throw new RuntimeException('resize_failed');
                    $tmp = tempnam($this->out, '.webp-'); if (!$tmp) throw new RuntimeException('write_failed');
                    if (!imagewebp($dest, $tmp, 82) || !$this->validWebp($tmp, $dw, $dh)) throw new RuntimeException('webp_validation');
                    imagedestroy($dest); $dest = null; $written = filesize($tmp);
                    if ($this->bytes + $written > self::MAX_BYTES) throw new RuntimeException('output_budget');
                    @chmod($tmp, 0644); if (!rename($tmp, $output)) throw new RuntimeException('write_failed');
                    $tmp = null; $this->bytes += $written;
                }
                $variants[$dw] = array('url' => self::OUT . '/' . $name, 'w' => $dw, 'h' => $dh, 'bytes' => filesize($output));
            }
        } finally {
            if ($source) imagedestroy($source); if ($dest) imagedestroy($dest); if ($tmp) $this->removeOutputTemp($tmp);
        }
        clearstatcache(true, $path);
        if (filemtime($path) !== $mtime || filesize($path) !== $size) throw new RuntimeException('source_changed');
        ksort($variants, SORT_NUMERIC);
        foreach (array_unique(array_filter(array($group['full'], $group['small']))) as $alias) {
            $aliasPath = $this->path($alias); if (!$aliasPath || !is_file($aliasPath)) continue;
            $bucket = 'map-' . substr(hash('sha256', $alias), 0, 2) . '.php'; $map = $this->read($bucket) ?: array();
            $map[$alias] = array('source' => $url, 'mtime' => $mtime, 'size' => $size, 'alias_mtime' => filemtime($aliasPath),
                'alias_size' => filesize($aliasPath), 'variants' => array_values($variants));
            $this->atomic($bucket, $map);
        }
    }
    private function report()
    {
        $s = $this->state ?: array();
        return array('probe' => $this->probe(), 'status' => $s['status'] ?? 'unprepared', 'phase' => $s['phase'] ?? null,
            'processed' => $s['cursor'] ?? 0, 'total' => $s['total'] ?? 0, 'ready' => $s['ready'] ?? 0, 'skipped' => $s['skipped'] ?? 0,
            'directories_scanned' => $s['dir_cursor'] ?? null, 'directories_found' => isset($s['dirs']) ? count($s['dirs']) : null,
            'output_mb' => round(($s['output_bytes'] ?? 0) / 1048576, 2), 'cpu_seconds' => round($s['cpu_seconds'] ?? 0, 2),
            'accounting_version' => $s['output_accounting_version'] ?? null, 'accounting_dirty' => $s['output_dirty'] ?? null,
            'accounting_full_scan' => $this->accountingFullScan, 'reasons' => $s['reasons'] ?? array());
    }
    public function execute($action, $helper)
    {
        if ($action === 'probe') return array('probe' => $this->probe());
        if ($action === 'batch') $action = 'run';
        $this->ready();
        try {
            if ($action === 'prepare') {
                if (!$this->probe()['gd_webp']) throw new RuntimeException('webp_unavailable');
                if (!$this->path('/upload/shop_1')) throw new RuntimeException('source_unavailable');
                $this->mkdirSafe($this->root . '/assets', 0755); $this->mkdirSafe($this->out, 0755);
                if (!$this->budget(true)) throw new RuntimeException('output_budget');
                if (!$this->state) {
                    $this->state = array('root_hash' => hash('sha256', $this->root), 'status' => 'paused', 'phase' => 'scan', 'dirs' => array('/upload/shop_1'),
                        'dir_cursor' => 0, 'groups' => array(), 'cursor' => 0, 'total' => 0, 'ready' => 0, 'skipped' => 0, 'cpu_seconds' => 0, 'reasons' => array(),
                        'prepare_complete' => false);
                }
                $this->markOutputDirty();
                $htaccess = "<IfModule mod_mime.c>\nAddType image/webp .webp\n</IfModule>\n<IfModule mod_headers.c>\n<FilesMatch \"\\.webp$\">\nHeader set Cache-Control \"public, max-age=31536000, immutable\"\nHeader set X-Content-Type-Options \"nosniff\"\n</FilesMatch>\n</IfModule>\n";
                $config = $this->out . '/.htaccess';
                if (is_link($config) || (is_file($config) && @file_get_contents($config) !== $htaccess)) throw new RuntimeException('output_config_conflict');
                if (!is_file($config)) {
                    if ($this->bytes + strlen($htaccess) > self::MAX_BYTES) throw new RuntimeException('output_budget');
                    $temp = tempnam($this->out, '.config-');
                    if (!$temp) throw new RuntimeException('write_failed');
                    try {
                        if (file_put_contents($temp, $htaccess) !== strlen($htaccess)) throw new RuntimeException('write_failed');
                        @chmod($temp, 0644); if (!rename($temp, $config)) throw new RuntimeException('write_failed');
                    } finally { $this->removeOutputTemp($temp); }
                }
                $this->atomic('images-render.php', str_replace("\r\n", "\n", $helper), true);
                $this->atomic('manifest.php', array('schema' => 1, 'root_hash' => hash('sha256', $this->root)));
                // Prepare is infrequent: reconcile again so a new config is charged exactly.
                if (!$this->budget(true)) throw new RuntimeException('output_budget');
                $this->state['prepare_complete'] = true;
                $this->save(true); return $this->report();
            }
            if (!$this->state) throw new RuntimeException('prepare_required');
            if ($action === 'pause') { if ($this->state['status'] !== 'done') $this->state['status'] = 'paused'; $this->atomic('state.php', $this->state); return $this->report(); }
            if ($action !== 'run') throw new RuntimeException('unknown_action');
            if (($this->state['prepare_complete'] ?? true) !== true) throw new RuntimeException('prepare_required');
            if (in_array($this->state['status'], array('done', 'cpu_limit', 'output_budget', 'failed'), true)) return $this->report();
            if (!$this->probe()['gd_webp']) throw new RuntimeException('webp_unavailable');
            if (!$this->budget()) { $this->state['status'] = 'output_budget'; $this->save(); return $this->report(); }
            if ($this->state['cpu_seconds'] >= 2400) { $this->state['status'] = 'cpu_limit'; $this->save(); return $this->report(); }
            $this->markOutputDirty();
            $this->state['status'] = 'running'; $this->reserve = str_repeat('r', 1048576); $this->active = true;
            register_shutdown_function(function () {
                $error = error_get_last();
                if (!$this->active || !$error || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
                $this->reserve = null; $this->state['output_dirty'] = true; $this->state['status'] = 'failed'; $this->countReason('fatal_worker_stop');
                try { $this->finishCpu(); $this->save(); } catch (Throwable $ignored) { }
                error_log('klimat_image_build: fatal_worker_stop');
            });
            if ($this->state['phase'] === 'scan') $this->scan();
            if ($this->state['phase'] === 'build' && !$this->expired()) {
                $queue = $this->read('queue.php'); if (!$queue) { if ($this->state['total']) throw new RuntimeException('invalid_queue'); $queue = array(); }
                $count = 0;
                while ($this->state['cursor'] < count($queue) && $count < 20 && !$this->expired()) {
                    try { $this->convert($queue[$this->state['cursor']]); $this->state['ready']++; }
                    catch (RuntimeException $e) {
                        $code = $e->getMessage();
                        if (in_array($code, array('output_budget', 'write_failed', 'output_conflict', 'output_cleanup_failed', 'symlink_rejected', 'invalid_state', 'state_memory_limit'), true)) throw $e;
                        $this->state['skipped']++; $this->countReason($code);
                    }
                    $this->state['cursor']++; $count++;
                }
                if ($this->state['cursor'] === count($queue)) $this->state['status'] = 'done';
            }
            $this->finishCpu();
            if ($this->state['cpu_seconds'] >= 2400 && $this->state['status'] !== 'done') $this->state['status'] = 'cpu_limit';
            $this->save(true); $this->active = false; return $this->report();
        } catch (Throwable $e) {
            if ($this->active) { $this->finishCpu(); $this->state['output_dirty'] = true; $this->state['status'] = $e->getMessage() === 'output_budget' ? 'output_budget' : 'failed'; $this->countReason('worker_stop'); $this->save(); $this->active = false; }
            throw $e;
        } finally { if ($this->lock) { flock($this->lock, LOCK_UN); fclose($this->lock); $this->lock = null; } }
    }
}

$kibHelper = <<<'KIB_EMBEDDED_HELPER_20261117'
<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/* Include once after XSL rendering; no GD, no writes, no changes to zoom links. */
if (!function_exists('klimat_modern_image_html')) {
function klimat_modern_image_html($html)
{
    if (!is_string($html) || !class_exists('DOMDocument')) return $html;
    try {
    $root = realpath(defined('CMS_FOLDER') ? CMS_FOLDER : (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ''));
    if (!$root || $root === DIRECTORY_SEPARATOR || is_link(__DIR__)) return $html;
    $read = static function ($file) {
        if (is_link($file) || !is_file($file) || filesize($file) > 2097152) return null;
        $raw = @file_get_contents($file);
        $guard = "<?php http_response_code(404); exit; __halt_compiler();\n";
        if ($raw === false || strncmp($raw, $guard, strlen($guard)) !== 0) return null;
        $data = json_decode(substr($raw, strlen($guard)), true);
        return is_array($data) ? $data : null;
    };
    $index = $read(__DIR__ . '/manifest.php');
    if (!$index || ($index['schema'] ?? 0) !== 1 || ($index['root_hash'] ?? '') !== hash('sha256', $root)) return $html;
    $buckets = $resolved = array();
    $safe = static function ($url) use ($root) {
        if (!is_string($url)) return false;
        if (!preg_match('~^/upload/shop_1/(?:[A-Za-z0-9_-]+/)*item_([0-9]+)/(?:small_)?(?:item_image([0-9]+)|shop_property_file_[0-9]+_[0-9]+)\.(?:jpe?g|png)$~iD', $url, $m)) return false;
        if (isset($m[2]) && $m[2] !== '' && $m[1] !== $m[2]) return false;
        $path = $root;
        foreach (explode('/', ltrim($url, '/')) as $part) { $path .= '/' . $part; if (is_link($path)) return false; }
        return is_file($path) && realpath($path) === $path ? $path : false;
    };
    $lookup = static function ($url) use ($root, $read, $safe, &$buckets, &$resolved) {
        if (array_key_exists($url, $resolved)) return $resolved[$url];
        $resolved[$url] = null;
        $alias = $safe($url); if (!$alias) return null;
        $bucket = substr(hash('sha256', $url), 0, 2);
        if (!array_key_exists($bucket, $buckets)) $buckets[$bucket] = $read(__DIR__ . '/map-' . $bucket . '.php');
        $entry = $buckets[$bucket][$url] ?? null;
        if (!is_array($entry)) return null;
        $source = $safe($entry['source'] ?? ''); if (!$source) return null;
        clearstatcache(true, $alias); clearstatcache(true, $source);
        if (@filemtime($alias) !== ($entry['alias_mtime'] ?? -1) || @filesize($alias) !== ($entry['alias_size'] ?? -1)
            || @filemtime($source) !== ($entry['mtime'] ?? -1) || @filesize($source) !== ($entry['size'] ?? -1)) return null;
        $variants = array();
        foreach (is_array($entry['variants'] ?? null) ? $entry['variants'] : array() as $v) {
            if (!is_array($v) || !is_string($v['url'] ?? null) || !preg_match('~^/assets/catalog-images-20261117/[a-f0-9]{64}\.webp$~D', $v['url'])
                || !is_int($v['w'] ?? null) || !is_int($v['h'] ?? null) || $v['w'] < 1 || $v['w'] > 1280 || $v['h'] < 1 || $v['h'] > 1280) continue;
            $path = $root . $v['url'];
            if (is_link($root . '/assets') || is_link($root . '/assets/catalog-images-20261117') || is_link($path)
                || !is_file($path) || @filesize($path) !== ($v['bytes'] ?? -1)) continue;
            $variants[$v['w']] = $v;
        }
        if (!$variants) return null;
        ksort($variants, SORT_NUMERIC); return $resolved[$url] = array_values($variants);
    };
    $srcset = static function ($variants) { return implode(', ', array_map(static function ($v) { return $v['url'] . ' ' . $v['w'] . 'w'; }, $variants)); };
    $choose = static function ($variants, $width) { foreach ($variants as $v) if ($v['w'] >= $width) return $v['url']; return end($variants)['url']; };
    $detailSizes = '(max-width: 760px) calc(100vw - 48px), (max-width: 1100px) 55vw, 680px';
    $cardSizes = '(max-width: 480px) calc((100vw - 72px) / 2), (max-width: 760px) calc((100vw - 64px) / 2), (max-width: 1100px) 30vw, 360px';
    $picture = 0;
    // Tokenize raw-text blocks first. Parse/serialize only individual opening tags, never the full document.
    $pattern = '~<!--[\s\S]*?-->|<(script|style|textarea|title)\b[^>]*>[\s\S]*?</\1\s*>|</?picture\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>|<(?:img|a)\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i';
    $result = preg_replace_callback($pattern, static function ($match) use (&$picture, $lookup, $srcset, $choose, $detailSizes, $cardSizes) {
        $tag = $match[0];
        if (preg_match('~^</picture\b~i', $tag)) { $picture = max(0, $picture - 1); return $tag; }
        if (preg_match('~^<picture\b~i', $tag)) { $picture++; return $tag; }
        if (!preg_match('~^<(img|a)\b~i', $tag, $type) || $picture) return $tag;
        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $tag . '</body></html>', LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$loaded) return $tag;
        $node = $doc->getElementsByTagName(strtolower($type[1]))->item(0); if (!$node) return $tag;
        if (strtolower($type[1]) === 'img') {
            if ($node->hasAttribute('data-src') || $node->hasAttribute('data-srcset')) return $tag;
            $variants = $lookup($node->getAttribute('src')); if (!$variants) return $tag;
            $isDetail = $node->getAttribute('id') === 'main-photo';
            $node->setAttribute('src', $choose($variants, $isDetail ? 720 : 360));
            $node->setAttribute('srcset', $srcset($variants));
            if (!$node->hasAttribute('sizes')) $node->setAttribute('sizes', $isDetail ? $detailSizes : $cardSizes);
        } else {
            if (!preg_match('~(?:^|\s)photo-thumb(?:\s|$)~', $node->getAttribute('class'))) return $tag;
            $variants = $lookup($node->getAttribute('href')); if (!$variants) return $tag;
            $node->setAttribute('data-display', $choose($variants, 720));
            $node->setAttribute('data-display-srcset', $srcset($variants));
            $node->setAttribute('data-display-sizes', $detailSizes);
        }
        $opening = '<' . strtolower($type[1]);
        foreach ($node->attributes as $attr) $opening .= ' ' . $attr->name . '="' . htmlspecialchars($attr->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        return $opening . '>';
    }, $html);
    return is_string($result) ? $result : $html;
    } catch (Throwable $ignored) { return $html; }
}
}
KIB_EMBEDDED_HELPER_20261117;

if ($kibPost) {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $worker = new KlimatImageBuild20261117();
        echo json_encode(array('ok' => true, 'result' => $worker->execute($action, $kibHelper)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        $code = preg_match('/^[a-z_]+$/D', $e->getMessage()) ? $e->getMessage() : 'internal_error';
        http_response_code($code === 'lock_busy' ? 409 : 500);
        error_log('klimat_image_build: ' . $code);
        echo json_encode(array('ok' => false, 'error' => $code, 'io_category' => isset($worker) ? $worker->ioCategory() : null));
    }
    return;
}
$kibCsrfJson = json_encode($kibToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
echo <<<'KIB_UI'
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Оптимизация изображений</title><style>body{font:15px/1.6 system-ui,sans-serif;max-width:900px;margin:40px auto;padding:0 20px;color:#182c37}button{padding:10px 18px;margin:0 8px 10px 0;cursor:pointer;border:1px solid #8aa8b1;border-radius:8px;background:#f0f7f8}pre{white-space:pre-wrap;background:#f3f6f8;padding:20px;border-radius:10px}small{color:#536774}</style></head><body><h1>Оптимизация фотографий каталога</h1><p>Оригиналы сохраняются. Новые WebP: до 360, 720 и 1280 px по длинной стороне. Очередь сохраняется после каждого пакета.</p><div><button data-action="probe">Проверить окружение</button><button data-action="prepare">Подготовить</button><button data-action="batch">Один пакет</button><button id="run">Запустить / продолжить</button><button id="pause">Пауза</button></div><p id="notice" role="status">Подготовьте очередь, затем запустите обработку.</p><pre id="result">Ожидание</pre><small>Автообработка работает только пока страница открыта. Ограничения: 600 MB файлов, 2400 секунд суммарного CPU; один пакет — до 20 групп, 2 CPU / 4 секунд между группами. Один файл может обрабатываться дольше.</small><script>
KIB_UI;
echo 'const csrf=' . $kibCsrfJson . ';';
echo <<<'KIB_JS'
let automatic=false,busy=false,timer=null,pauseRequested=false;
const result=document.getElementById('result'),notice=document.getElementById('notice');
async function call(action){if(busy)return;busy=true;try{const response=await fetch(location.pathname,{method:'POST',credentials:'same-origin',cache:'no-store',body:new URLSearchParams({action:action,csrf:csrf}),headers:{'Accept':'application/json'}});if(response.status===403){throw new Error('Сессия завершена или доступ закрыт. Обновите страницу.')}const data=await response.json();result.textContent=JSON.stringify(data,null,2);if(!data.ok){if(data.error==='lock_busy'&&automatic){notice.textContent='Другой пакет ещё выполняется.';return}throw new Error(data.error||'Ошибка')}const status=data.result.status;notice.textContent=status==='done'?'Обработка завершена.':(status||'Проверка завершена.');if(['done','cpu_limit','output_budget','failed'].includes(status))automatic=false;}catch(error){automatic=false;notice.textContent=error.message;}finally{busy=false;if(pauseRequested){pauseRequested=false;await call('pause')}else if(automatic){timer=setTimeout(()=>call('run'),1000)}}}
document.querySelectorAll('[data-action]').forEach(button=>button.addEventListener('click',()=>{automatic=false;clearTimeout(timer);call(button.dataset.action)}));
document.getElementById('run').addEventListener('click',()=>{automatic=true;clearTimeout(timer);call('run')});
document.getElementById('pause').addEventListener('click',()=>{automatic=false;clearTimeout(timer);if(busy){pauseRequested=true;notice.textContent='Пауза после текущего пакета.'}else call('pause')});
window.addEventListener('pagehide',()=>{automatic=false;clearTimeout(timer)});
call('probe');
</script></body></html>
KIB_JS;
