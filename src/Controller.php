<?php

namespace AwemaPL\Docs;

use Illuminate\Support\Str;
use AwemaPL\Docs\Facades\Docs;
use Symfony\Component\Yaml\Yaml;
use League\CommonMark\{Converter, DocParser, Environment, Extension\Table\TableExtension, HtmlRenderer};
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected $converter;

    public function __construct()
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new TableExtension());

        $this->converter = new Converter(new DocParser($environment), new HtmlRenderer($environment));
    }

    /**
     * @param string|null $package
     * @return \Illuminate\View\View
     */
    public function showComponents($category, ?string $package = null)
    {
        if (!empty($package)) {
            $package = explode('/', $package);
            switch (count($package)) {
                case 1:
                    return $this->showPackage($package, $category);
                case 2:
                    return $this->showVersion($package, $category);
                case 3:
                    return $this->showFile($package, $category);
                default:
                    abort(404);
            }
        }

        abort(404);
    }

    /**
     * @param string|null $path
     * @return \Illuminate\View\View
     */
    public function showStatic(?string $path = null)
    {
        $filepath = Docs::path($path ?? '');
        if (is_dir($filepath)) {
            $filepath = Str::finish($filepath, '/') . 'index.md';
            if (is_null($path)) {
                $path = config('docs.route.prefix');
            }
            $path = Str::after($path, '/');
        } else {
            $filepath .= '.md';
            $path = '';
        }
        try {
            $content = file_get_contents($filepath);
            $content = $this->converter->convertToHtml($content);
            extract($this->proceedContent($content, $path));
        } catch (\Exception $e) {
            abort(404);
        }

        $current_file = str_replace(Docs::path(), '/docs/', $filepath);
        $sidebar = $this->getNavs();

        return view(config('docs.static_view', 'docs::document'), compact('content', 'sidebar', 'h1', 'current_file'));
    }

    /**
     * @param array $path
     * @return \Illuminate\View\View
     */
    protected function showPackage(array $path, $category)
    {
        if (empty($redirect = Docs::getCache($path))) {
            $package = Docs::package($path[0], false);
            if (empty($package)) {
                abort(404);
            }
            $version = collect($package['versions'])->sortByDesc('name')->first()['name'];
            $redirect = $package['name'] . '/' . $version;
            Docs::setCache($redirect, $path);
        }

        return redirect(route('docs.components', [$category, $redirect]));
    }

    /**
     * @param array $path
     * @return \Illuminate\View\View
     */
    protected function showVersion(array $path, $category)
    {
        if (empty($data = Docs::getCache($path))) {
            if (empty(Docs::version($path[0], $path[1], false))) {
                abort(404);
            }
            $package = Docs::package($path[0], false);
            $versions = $this->getVersions($package, $category, $path[1]);
            $content = Docs::fileContent($package['name'], $path[1], 'index.md');
            $content = $this->converter->convertToHtml($content);
            extract($this->proceedContent($content, $path[1]));
            $package = [
                'name' => $package['name'],
                'version' => $path[1]
            ];
            $current_file = "/docs/components/{$package['name']}/{$path[1]}/index.md";
            $sidebar = $this->getNavs();

            $data = compact('content', 'sidebar', 'h1', 'package', 'versions', 'current_file');
            Docs::setCache($data, $path);
        }

        return view(config('docs.package_view', 'docs::document'), $data);
    }

    /**
     * @param array $path
     * @return \Illuminate\View\View
     */
    protected function showFile(array $path, $category)
    {
        if (Str::endsWith($path[2], '.md')) {
            $url = Str::replaceLast('.md', '', url()->current());
            return redirect($url, 301);
        }
        $path[2] = $path[2] . '.md';
        if ($path[2] == 'index.md') {
            return redirect(route('docs.components', [$category, $path[0] . '/' . $path[1]]));
        }
        if (empty($data = Docs::getCache($path))) {
            $content = Docs::fileContent($path[0], $path[1], $path[2]);
            if (empty($content)) {
                abort(404);
            }
            $package = Docs::package($path[0], false);
            $versions = $this->getVersions($package, $category, $path[1]);
            $content = $this->converter->convertToHtml($content);
            extract($this->proceedContent($content));
            $package = [
                'name' => $package['name'],
                'version' => $path[1]
            ];
            $current_file = "/docs/components/{$package['name']}/{$path[1]}/{$path[2]}";
            $sidebar = $this->getNavs();

            $data = compact('content', 'sidebar', 'h1', 'package', 'versions', 'current_file');
            Docs::setCache($data, $path);
        }

        return view(config('docs.package_view', 'docs::document'), $data);
    }

    /**
     * @return array
     */
    protected function getNavs(): array
    {
        $prefix = Str::start(config('docs.route.prefix'), '/');
        $navs = Docs::path('pages.yml');
        $navs = Yaml::parseFile($navs);
        
        return $this->buildNavs($navs, $prefix);
    }

    /**
     * @param array $navs
     * @param string $prefix
     * @return array
     */
    protected function buildNavs(array $navs, string $prefix): array
    {
        $prefix = Str::start($prefix, '/');
        $prefix = Str::finish($prefix, '/');
        foreach ($navs as &$nav) {
            $cprefix = $prefix;
            if (key_exists('link', $nav)) {
                $cprefix = $nav['link'] = $prefix . $nav['link'];
            }
            if (key_exists('slug', $nav)) {
                $cprefix = $prefix . $nav['slug'];
            }
            $isActiveComponent = Str::startsWith(request()->path(), trim($cprefix, '/'));
            $isActive = (request()->path() == trim($cprefix, '/'));
            if (($this->isComponent($cprefix) && $isActiveComponent) || $isActive) {
                $nav['active'] = true;
            };
            if (!empty($nav['children'])) {
                $nav['children'] = $this->buildNavs($nav['children'], $cprefix);
            }
        }

        return $navs;
    }

    /**
     * @param string $prefix
     * @return bool
     */
    protected function isComponent(string $prefix): bool
    {
        $components_path = config('docs.route.prefix');
        $components_path = Str::start($components_path, '/');
        $components_path = Str::finish($components_path, '/');
        $components_path .= 'components';

        return Str::startsWith($prefix, $components_path);
    }

    /**
     * @param array $package
     * @param string|null $current
     * @return array
     */
    protected function getVersions(array $package, $category, ?string $current = null): array
    {
        $ckey = ['versions', $package['name'], $current];
        if (empty($versions = Docs::getCache($ckey))) {
            if (empty($current)) {
                $current = collect($package['versions'])->sortByDesc('name')->first()['name'];
            }
            $versions = [];
            foreach ($package['versions'] as $version) {
                $versions[] = [
                    'link' => route('docs.components', [$category, implode('/', [
                        $package['name'],
                        $version['name']
                    ])]),
                    'title' => $version['name'],
                    'selected' => $version['name'] === $current
                ];
            }
            Docs::setCache($versions, $ckey);
        }

        return $versions;
    }

    /**
     * Proceed content
     *
     * @param string $content
     * @param string $path
     * @return array
     */
    protected function proceedContent(string $content, string $path = ''): array
    {
        $content = $this->wrapContentTable($content);
        $content = $this->fixPre($content);
        $content = $this->fixUrls($content, $path);
        return $this->separateHeader($content);
    }

    /**
     * Wrap content table
     *
     * @param string $content
     * @return string
     */
    protected function wrapContentTable(string $content): string
    {
        $content = str_replace('<table>', '<div class="md-table"><table>', $content);
        $content = str_replace('</table>', '</table></div>', $content);
        return $content;
    }

    /**
     * Fix pre tag
     *
     * @param string $content
     * @return string
     */
    protected function fixPre(string $content): string
    {
        $content = str_replace('<pre>', '<pre v-pre>', $content);
        return $content;
    }

    protected function fixUrls(string $content, string $path = ''): string
    {
        if (!empty($path)) {
            $start = '<a href="./';
            $content = str_replace($start, Str::finish($start . $path, '/'), $content);
        }
        $content = str_replace('.md">', '">', $content);

        return $content;
    }

    /**
     * Extract h1 from content
     *
     * @param string $content
     * @return array
     */
    protected function separateHeader(string $content)
    {
        $h1 = trim($this->str_between($content, '<h1>', '</h1>'));
        $content = trim(str_replace('<h1>' . $h1 . '</h1>', '', $content));
        return compact('content', 'h1');
    }

    /**
     * Return string between
     *
     * @param string $string
     * @param string $start
     * @param string $end
     * @param bool $strict
     * @return bool|string
     */
    protected function str_between(string $string, string $start, string $end, bool $strict = true)
    {
        if ($strict && (!Str::contains($string, $start) || !Str::contains($string, $end))) {
            return false;
        }
        $string = Str::replaceFirst(Str::before($string, $start) . $start, '', $string);
        $string = Str::replaceFirst($end . Str::after($string, $end), '', $string);

        return $string;
    }
}
