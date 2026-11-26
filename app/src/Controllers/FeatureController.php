<?php

namespace App\Controllers;

use App\Features\FeatureRegistry;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\Security;

/**
 * Public pages of the features: features/ (all), features/<key> (live
 * example and code).
 *
 */
class FeatureController extends BaseController
{
    private static array $url_handlers = [
        '$Key' => 'index',
    ];

    public function index(HTTPRequest $request)
    {
        $key = (string) $request->param('Key');
        if ($key === '') {
            return $this->customise(['Title' => 'Features'])->renderWith([self::class, 'Page']);
        }
        $registry = FeatureRegistry::singleton();
        $feature = $registry->get($key);
        if (!$feature) {
            return $this->httpError(404);
        }
        $keys = array_keys($registry->all());
        $index = (int) array_search($key, $keys, true);
        $neighbour = function (int $i) use ($keys, $registry): ?ArrayData {
            $key = $keys[$i] ?? null;
            if (!$key) {
                return null;
            }
            return ArrayData::create(['Title' => $registry->get($key)['title'] ?? $key, 'Link' => 'features/' . $key]);
        };
        return $this->customise([
            'Title' => (string) ($feature['title'] ?? $key),
            'Feature' => ArrayData::create([
                'Key' => $key,
                'Title' => (string) ($feature['title'] ?? $key),
                'Group' => (string) ($feature['group'] ?? ''),
                'Summary' => (string) ($feature['summary'] ?? ''),
                'Points' => ArrayList::create(array_map(
                    fn ($point) => ArrayData::create(['Text' => (string) $point]),
                    (array) ($feature['points'] ?? [])
                )),
                'LiveLink' => $registry->liveLink($feature),
                'DocsLink' => isset($feature['docs'])
                    ? FeatureRegistry::config()->get('docs_base') . $feature['docs']
                    : null,
                'Snippets' => $registry->snippets($feature),
            ]),
            'Previous' => $index > 0 ? $neighbour($index - 1) : null,
            'Next' => $neighbour($index + 1),
            'LoggedIn' => (bool) Security::getCurrentUser(),
        ])->renderWith([self::class . '_show', 'Page']);
    }

    /**
     * @return ArrayList<ArrayData>
     */
    public function FeatureGroups(): ArrayList
    {
        $groups = [];
        foreach (FeatureRegistry::singleton()->all() as $key => $feature) {
            $group = (string) ($feature['group'] ?? 'More');
            $groups[$group][] = ArrayData::create([
                'Key' => $key,
                'Title' => (string) ($feature['title'] ?? $key),
                'Summary' => (string) ($feature['summary'] ?? ''),
                'Link' => 'features/' . $key,
            ]);
        }
        $list = ArrayList::create();
        foreach ($groups as $title => $features) {
            $list->push(ArrayData::create(['Title' => $title, 'Features' => ArrayList::create($features)]));
        }
        return $list;
    }
}
