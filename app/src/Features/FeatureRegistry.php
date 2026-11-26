<?php

namespace App\Features;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * The features shown in the demo (app/_config/features.yml): title, group,
 * summary, the live example and the code that builds it.
 *
 * Code is cut from the project's own files between markers, so the demo
 * always shows the code that actually runs:
 *
 *   // [demo:filters]            PHP, JS          # [demo:filters]     YAML
 *   ...                                           <%-- [demo:filters] --%>   templates
 *   // [/demo:filters]
 *
 * A region may occur several times in a file; the parts are joined with "…".
 */
class FeatureRegistry implements TemplateGlobalProvider
{
    use Injectable;
    use Configurable;

    /**
     * key => [title, group, summary, live, live_record, match, docs, code: [[file, region, caption]]]
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $features = [];

    /**
     * Base URL of the kit's documentation (links "Documentation").
     */
    private static string $docs_base = 'https://github.com/atwx/silverstripe-frontdesk-kit/blob/main/docs/';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return (array) static::config()->get('features');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Link to the live example: "live", or the view of the first record of
     * "live_record" (class) with "live" as pattern ("projects/view/{ID}").
     */
    public function liveLink(array $feature): string
    {
        $live = (string) ($feature['live'] ?? '');
        $class = (string) ($feature['live_record'] ?? '');
        if ($class !== '' && class_exists($class)) {
            /** @var DataObject|null $record */
            $record = $class::get()->sort('ID')->first();
            return str_replace('{ID}', (string) ($record?->ID ?? 0), $live);
        }
        return $live;
    }

    /**
     * Code snippets of a feature: File, Caption, Language, Code.
     *
     * @return ArrayList<ArrayData>
     */
    public function snippets(array $feature): ArrayList
    {
        $snippets = ArrayList::create();
        foreach ((array) ($feature['code'] ?? []) as $entry) {
            $file = (string) ($entry['file'] ?? '');
            $code = $this->extract($file, (string) ($entry['region'] ?? ''));
            if ($code === '') {
                continue;
            }
            $snippets->push(ArrayData::create([
                'File' => $file,
                'Caption' => (string) ($entry['caption'] ?? ''),
                'Language' => self::language($file),
                'Code' => $code,
                'GitHubLink' => 'https://github.com/atwx/silverstripe-frontdesk-demo/blob/main/' . $file,
            ]));
        }
        return $snippets;
    }

    /**
     * The lines of a region of a project file (without the markers and
     * without nested markers), dedented; "" when the file or region is missing.
     */
    public function extract(string $file, string $region): string
    {
        $path = Director::baseFolder() . '/' . ltrim($file, '/');
        if ($region === '' || str_contains($file, '..') || !is_file($path)) {
            return '';
        }
        $parts = [];
        $current = null;
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_contains($line, '[demo:' . $region . ']')) {
                $current = [];
                continue;
            }
            if (str_contains($line, '[/demo:' . $region . ']')) {
                if ($current !== null) {
                    $parts[] = self::dedent($current);
                }
                $current = null;
                continue;
            }
            if ($current !== null && !preg_match('#\[/?demo:[a-z0-9_-]+\]#', $line)) {
                $current[] = $line;
            }
        }
        $separator = match (self::language($file)) {
            'yaml' => '# …',
            'html' => '<%-- … --%>',
            default => '// …',
        };
        return implode("\n\n" . $separator . "\n\n", array_filter($parts));
    }

    /**
     * @param array<int, string> $lines
     */
    private static function dedent(array $lines): string
    {
        $indent = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $length = strlen($line) - strlen(ltrim($line, ' '));
            $indent = $indent === null ? $length : min($indent, $length);
        }
        $lines = array_map(fn ($line) => substr($line, (int) $indent), $lines);
        return trim(implode("\n", $lines), "\n");
    }

    private static function language(string $file): string
    {
        return match (pathinfo($file, PATHINFO_EXTENSION)) {
            'php' => 'php',
            'yml', 'yaml' => 'yaml',
            'ss', 'html' => 'html',
            'js' => 'javascript',
            'css', 'scss' => 'css',
            default => 'plaintext',
        };
    }

    /**
     * Features whose live example is the current page (config "match":
     * regular expressions on the URL path, default: the path of "live").
     *
     * @return ArrayList<ArrayData>
     */
    public static function DemoFeaturesHere(): ArrayList
    {
        $registry = self::singleton();
        $controller = Controller::curr();
        $path = $controller ? trim((string) $controller->getRequest()->getURL(), '/') : '';
        $list = ArrayList::create();
        if (str_starts_with($path, 'features')) {
            return $list;
        }
        foreach ($registry->all() as $key => $feature) {
            $patterns = (array) ($feature['match'] ?? []);
            if (!$patterns) {
                $live = trim((string) parse_url((string) ($feature['live'] ?? ''), PHP_URL_PATH), '/');
                $patterns = $live === '' ? ['^$'] : ['^' . preg_quote($live, '#') . '$'];
            }
            foreach ($patterns as $pattern) {
                if (preg_match('#' . $pattern . '#', $path)) {
                    $list->push(ArrayData::create([
                        'Key' => $key,
                        'Title' => $feature['title'] ?? $key,
                        'Link' => 'features/' . $key,
                    ]));
                    break;
                }
            }
        }
        return $list;
    }

    /**
     * @return array<string, string>
     */
    public static function get_template_global_variables(): array
    {
        return ['DemoFeaturesHere' => 'DemoFeaturesHere'];
    }
}
