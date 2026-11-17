<?php
// Standalone CLI tests; use a scratch directory, never a live CMS directory/database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!function_exists('mb_strlen')) { fwrite(STDERR, "mbstring is required\n"); exit(2); }
define('HOSTCMS', true);
$proposalRoot = is_file(__DIR__ . '/hostcms-facet-cache-adapter.php') ? __DIR__ : dirname(__DIR__);
require_once $proposalRoot . '/hostcms-facet-cache-adapter.php';
$directory = sys_get_temp_dir() . '/klimat-facet-cache-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$clock = 1000; $calls = 0; $passed = array(); $skipped = array();
$cache = new KlimatFacetOptionsCache20261117($directory, 300, static function () use (&$clock) { return $clock; });
$definitions = array(
    array('id' => 22, 'name' => 'Инверторное управление', 'type' => 1, 'list_id' => 0),
    array('id' => 28, 'name' => 'Охлаждающая способность, тыс. btu', 'type' => 1, 'list_id' => 0),
    array('id' => 39, 'name' => 'Wi-fi управление', 'type' => 1, 'list_id' => 0)
);
$values = array(22 => array('Да', 'Нет'), 28 => array('9 (25-30 м.кв)'), 39 => array('Да'));
$file = $directory . '/facet-options.php';
$key = KlimatFacetOptionsCache20261117::key(2, 1, 'recipe-v1', $definitions);
$build = static function () use (&$calls, &$values) { $calls++; return $values; };
function check($condition, string $name): void
{
    global $passed;
    if (!$condition) throw new RuntimeException($name);
    $passed[] = $name;
}
function readEnvelope(string $file): array
{
    $raw = file_get_contents($file);
    $guard = KlimatFacetOptionsCache20261117::DATA_GUARD;
    if (strncmp($raw, $guard, strlen($guard)) !== 0) throw new RuntimeException('guard missing');
    return json_decode(substr($raw, strlen($guard)), true, 16, JSON_THROW_ON_ERROR);
}

try {
    check($cache->remember($key, $build) === $values && $calls === 1, 'cold miss builds once');
    check($cache->remember($key, $build) === $values && $calls === 1, 'warm hit avoids builder');
    $clock = 1299;
    $cache->remember($key, $build);
    check($calls === 1, 'TTL is valid at created+299');
    $clock = 1300;
    $cache->remember($key, $build);
    check($calls === 2, 'TTL expires exactly at created+300');
    $clock = 1200;
    $cache->remember($key, $build);
    check($calls === 3, 'clock rollback rejects future creation time');

    $reordered = array_reverse($definitions);
    check(KlimatFacetOptionsCache20261117::key(2, 1, 'recipe-v1', $reordered) === $key, 'definition order does not fragment key');
    $changed = $definitions; $changed[0]['list_id'] = 9;
    check(KlimatFacetOptionsCache20261117::key(2, 1, 'recipe-v1', $changed) !== $key, 'definition metadata changes key');
    check(KlimatFacetOptionsCache20261117::key(2, 1, 'recipe-v2', $definitions) !== $key, 'generation changes key');
    $rejected = false;
    try { KlimatFacetOptionsCache20261117::key(2, 1, 'recipe-v1', array_merge($definitions, array(array('id' => 99)))); }
    catch (InvalidArgumentException $expected) { $rejected = true; }
    check($rejected, 'extra definition/private namespace rejected');

    $empty = array(22 => array(), 28 => array(), 39 => array());
    $emptyKey = KlimatFacetOptionsCache20261117::key(2, 1, 'empty', $definitions);
    $emptyCalls = 0;
    $emptyBuild = static function () use ($empty, &$emptyCalls) { $emptyCalls++; return $empty; };
    $cache->remember($emptyKey, $emptyBuild); $cache->remember($emptyKey, $emptyBuild);
    check($emptyCalls === 1, 'empty values are a cache hit');

    $previous = file_get_contents($file);
    $private = $values; $private['customer'] = array('must-never-be-cached');
    $privateKey = KlimatFacetOptionsCache20261117::key(2, 1, 'privacy', $definitions);
    $cache->remember($privateKey, static function () use ($private) { return $private; });
    check(file_get_contents($file) === $previous && strpos(file_get_contents($file), 'must-never-be-cached') === false, 'extra private payload is never persisted');
    $large = array_fill(0, 200, str_repeat('😀', 80));
    $largeValues = array(22 => $large, 28 => $large, 39 => $large);
    $cache->remember($privateKey, static function () use ($largeValues) { return $largeValues; });
    check(file_get_contents($file) === $previous, 'valid-shaped oversized JSON is not persisted');

    file_put_contents($file, str_repeat('x', KlimatFacetOptionsCache20261117::MAX_FILE_BYTES + 1));
    $cache->remember($key, $build);
    check(readEnvelope($file)['values'] === $values && filesize($file) <= KlimatFacetOptionsCache20261117::MAX_FILE_BYTES, 'oversized/corrupt file falls back and is replaced boundedly');
    file_put_contents($file, KlimatFacetOptionsCache20261117::DATA_GUARD . '{broken');
    $cache->remember($key, $build);
    check(readEnvelope($file)['values'] === $values, 'malformed JSON falls back');

    $held = fopen($directory . '/facet-options.lock.php', 'c+b'); flock($held, LOCK_EX);
    $before = file_get_contents($file); $beforeCalls = $calls;
    $differentKey = KlimatFacetOptionsCache20261117::key(2, 1, 'locked', $definitions);
    $cache->remember($differentKey, $build);
    check($calls === $beforeCalls + 1 && file_get_contents($file) === $before, 'lock contention uses fresh builder without cache write');
    flock($held, LOCK_UN); fclose($held);
    file_put_contents($directory . '/facet-options.write.php', KlimatFacetOptionsCache20261117::DATA_GUARD . '{partial');
    $cache->remember($differentKey, $build);
    check(!file_exists($directory . '/facet-options.write.php') && readEnvelope($file)['key'] === $differentKey, 'orphan fixed temp recovered under lock');
    $failureCalls = 0; $propagated = false;
    try {
        $failureKey = KlimatFacetOptionsCache20261117::key(2, 1, 'native-error', $definitions);
        $cache->remember($failureKey, static function () use (&$failureCalls) { $failureCalls++; throw new RuntimeException('native-builder-error'); });
    } catch (RuntimeException $expected) { $propagated = $expected->getMessage() === 'native-builder-error'; }
    check($propagated && $failureCalls === 1, 'native builder exception propagates without a duplicate query');
    check($cache->invalidate() && !file_exists($file), 'explicit invalidation removes only fixed cache file');

    $unavailable = new KlimatFacetOptionsCache20261117($directory . '/not-provisioned', 300);
    check($unavailable->remember($key, $build) === $values && !file_exists($directory . '/not-provisioned'), 'missing storage does not mkdir or break fresh results');

    // No CURRENT_SITE yet: the adapter must not touch shop fields or query APIs.
    check(klimat_public_facet_values_20261117(new stdClass(), array(), $directory) === null, 'undefined CURRENT_SITE is native-fallback NULL');
    define('CURRENT_SITE', 2);
    $shop = (object) array('id' => 1, 'site_id' => 2);
    $models = array_map(static function ($d) { return (object) $d; }, $definitions);
    $models[] = (object) array('id' => 100, 'name' => 'Not allowlisted', 'type' => 1);
    class Core_QueryBuilder
    {
        public static $executions = 0;
        public static function select($column) { return new FacetQueryFixture(); }
    }
    class FacetQueryFixture
    {
        private $id;
        public function from($table) { if ($table !== 'property_value_strings') throw new RuntimeException('unexpected table'); return $this; }
        public function where($column, $operator, $value) { if ($column === 'property_id') $this->id = $value; return $this; }
        public function groupBy($unused) { return $this; }
        public function orderBy($unused, $direction) { return $this; }
        public function limit($limit) { if ($limit !== 200) throw new RuntimeException('unbounded query'); return $this; }
        public function execute() { Core_QueryBuilder::$executions++; return $this; }
        public function asAssoc() { return $this; }
        public function result() { return array(array('value' => 'value-' . $this->id)); }
    }
    $first = klimat_public_facet_values_20261117($shop, $models, $directory);
    $second = klimat_public_facet_values_20261117($shop, array_reverse($models), $directory);
    check($first === $second && Core_QueryBuilder::$executions === 3, 'adapter reuses scoped definitions; hit avoids three DISTINCT queries');
    $listModels = $models; $listModels[0] = clone $listModels[0]; $listModels[0]->type = 3;
    check(klimat_public_facet_values_20261117($shop, $listModels, $directory) === null && Core_QueryBuilder::$executions === 3, 'list/type change preserves native fallback without query mutation');

    if (function_exists('pcntl_fork')) {
        $pid = pcntl_fork();
        if ($pid === -1) throw new RuntimeException('fork failed');
        if ($pid === 0) {
            try {
                for ($i = 0; $i < 100; $i++) {
                    $atomicKey = KlimatFacetOptionsCache20261117::key(2, 1, 'atomic-' . ($i % 2), $definitions);
                    $cache->remember($atomicKey, $build);
                }
                exit(0);
            } catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }
        }
        $reads = 0;
        do {
            readEnvelope($file); $reads++;
            $finished = pcntl_waitpid($pid, $status, WNOHANG);
            if ($finished === 0) usleep(1000);
        } while ($finished === 0);
        check($reads > 0 && pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'concurrent reader sees only complete guarded JSON replacements');
    } else $skipped[] = 'atomic reader/writer stress: pcntl unavailable';

    echo json_encode(array('passed' => $passed, 'skipped' => $skipped), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} finally {
    foreach (array('facet-options.php', 'facet-options.lock.php', 'facet-options.write.php') as $name) {
        $path = $directory . '/' . $name;
        if (is_file($path) && !is_link($path)) unlink($path);
    }
    rmdir($directory);
}
