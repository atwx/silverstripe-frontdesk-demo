<?php

namespace App\Controllers;

use App\Features\FeatureRegistry;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

/**
 * Public start page: what the kit is, the demo login and all features with
 * links to the live example and the code.
 *
 */
class MainController extends BaseController
{
    public function index(HTTPRequest $request)
    {
        return ['Title' => 'frontdesk-kit demo'];
    }

    /**
     * Features by group.
     *
     * @return ArrayList<ArrayData>
     */
    public function FeatureGroups(): ArrayList
    {
        return FeatureController::singleton()->FeatureGroups();
    }
}
