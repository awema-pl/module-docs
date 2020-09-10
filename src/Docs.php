<?php

namespace AwemaPL\Docs;

use Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;

class Docs
{
    protected $cache;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->cache = config('docs.cache', false);
    }

    /**
     * Register docs routes
     */
    public function routes()
    {
        $config = config('docs.route', []);
        $config['namespace'] = Str::start(__NAMESPACE__, '\\');
        $config['as'] = 'docs.';
        Route::group($config, function () {
            Route::get('{category}/{package}', 'Controller@showComponents')->where('package', '.*')->name('components');
            Route::get('{path?}', 'Controller@showStatic')->where('path', '.*')->name('docs');
        });
    }

    /**
     * Get all stored packages
     *
     * @param bool $withDocs
     * @return array
     */
    public function all($withDocs = true): array
    {
        return $this->get(null, $withDocs);
    }

    /**
     * Get specified packages
     *
     * @param mixed ...$args
     * @return array
     */
    public function get(...$args): array
    {
        $withDocs = true;
        if (is_bool(end($args))) {
            $withDocs = array_pop($args);
        }
        $packages = $this->getPackagesFromArgs($args);

        return $this->getStoredPackages($packages, $withDocs);
    }

    /**
     * Get list of packages|versions|files as array or file content
     *
     * @param string|null $package
     * @param string|null $version
     * @param string|null $file
     * @return array|string
     */
    public function list(?string $package = null, ?string $version = null, ?string $file = null)
    {
        if (empty($package)) {
            return collect($this->get(false))->pluck('name')->toArray();
        } elseif (empty($version)) {
            $versions = $this->versions($package, false);
            return collect($versions)->pluck('name')->toArray();
        } elseif (empty($file)) {
            $files = $this->files($package, $version, false);
            return collect($files)->pluck('name')->toArray();
        } else {
            return $this->fileContent($package, $version, $file);
        }
    }

    /**
     * Get package data
     *
     * @param string $package
     * @param bool $withDocs
     * @return array|null
     */
    public function package(string $package, bool $withDocs = true): ?array
    {
        return collect($this->get($package, $withDocs))->firstWhere('name', $package);
    }

    /**
     * Get package versions with data
     *
     * @param string $package
     * @param bool $withDocs
     * @return array|null
     */
    public function versions(string $package, bool $withDocs = true): ?array
    {
        $package = $this->package($package, $withDocs);
        return $package['versions'];
    }

    /**
     * Get version data
     *
     * @param string $package
     * @param string $version
     * @param bool $withDocs
     * @return array|null
     */
    public function version(string $package, string $version, bool $withDocs = true): ?array
    {
        $package = collect($this->get([$package => $version], $withDocs))->firstWhere('name', $package);
        $versions = collect($package['versions']);
        return $versions->firstWhere('name', $version);
    }

    /**
     * Get version files
     *
     * @param string $package
     * @param string $version
     * @param bool $withDocs
     * @return array|null
     */
    public function files(string $package, string $version, bool $withDocs = true): ?array
    {
        $version = $this->version($package, $version, $withDocs);
        return $version['files'];
    }

    /**
     * Get file
     *
     * @param string $package
     * @param string $version
     * @param string $file
     * @return array|null
     */
    public function file(string $package, string $version, string $file): ?array
    {
        $files = $this->files($package, $version);
        return collect($files)->firstWhere('name', $file);
    }

    /**
     * Get file content
     *
     * @param string $package
     * @param string $version
     * @param string $file
     * @return string|null
     */
    public function fileContent(string $package, string $version, string $file): ?string
    {
        $file = $this->file($package, $version, $file);
        return $file['content'];
    }

    /**
     * @param string|array $path
     * @return mixed
     */
    public function getCache($path)
    {
        if ($this->cache) {
            return Cache::get($this->getCacheKey($path));
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param string|array $path
     */
    public function setCache($data, $path)
    {
        if ($this->cache) {
            Cache::forever($this->getCacheKey($path), $data);
        }
    }

    /**
     * @param string|array $key
     * @return string
     */
    protected function getCacheKey($key)
    {
        if (is_array($key)) {
            $key = implode('_', $key);
        }

        return config('docs.route.prefix') . $key;
    }

    /**
     * Extract packages names and versions from args
     *
     * @param array $args
     * @return array|null
     */
    protected function getPackagesFromArgs(array $args): ?array
    {
        $packages = [];
        foreach ($args as $argument) {
            $packages = array_merge($packages, $this->getPackageFromArg($argument));
        }
        if (empty($packages)) {
            $packages = null;
        }

        return $packages;
    }

    /**
     * Extract package name and versions from arg
     *
     * @param mixed $argument
     * @return array|null
     */
    protected function getPackageFromArg($argument): ?array
    {
        $packages = [];
        if (is_array($argument)) {
            foreach ($argument as $package => $versions) {
                if (is_string($package)) {
                    $versions = Arr::wrap($versions);
                    $packages[$package] = $versions;
                } else {
                    $package = $versions;
                    $packages[$package] = null;
                }
            }
        } elseif (!empty($argument)) {
            $package = $argument;
            $packages[$package] = null;
        }

        return $packages;
    }

    /**
     * Get stored packages
     *
     * @param array|null $list
     * @param bool $withDocs
     * @return array
     */
    protected function getStoredPackages(?array $list = null, $withDocs = true): array
    {
        $fs = new Filesystem;
        $dirs = $fs->directories($this->path('components'));
        $r_packages = [];
        foreach ($dirs as $dir) {
            $package = basename($dir);
            //If current package is not in specified package list
            if (!empty($list) && !array_key_exists($package, $list)) {
                continue;
            }
            $subdirs = $fs->directories($dir);
            $r_versions = [];
            foreach ($subdirs as $subdir) {
                $version = basename($subdir);
                //If current version is not in specified version list
                if (!empty($list[$package]) && !in_array($version, $list[$package])) {
                    continue;
                }
                $r_files = [];
                $files = $fs->files($subdir);
                foreach ($files as $file) {
                    $r_files[] = [
                        'name' => $file->getBasename(),
                        'content' => $withDocs ? file_get_contents($file->getPathName()) : null
                    ];
                }
                $r_versions[] = [
                    'name' => $version,
                    'files' => $r_files
                ];
            }
            $r_packages[] = [
                'name' => $package,
                'versions' => $r_versions
            ];
        }

        return $r_packages;
    }

    /**
     * @param string $path
     * @return string
     */
    public function path(string $path = '')
    {
        return config('docs.path') . Str::start($path, '/');
    }
}
