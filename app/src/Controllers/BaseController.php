<?php

namespace App\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\View\SSViewer;

/**
 * Class \App\Controllers\BaseController
 *
 */
class BaseController extends Controller
{
    public function getViewer($action)
    {
        // Hard-coded templates
        if (isset($this->templates[$action]) && $this->templates[$action]) {
            $templates = $this->templates[$action];
        } elseif (isset($this->templates['index']) && $this->templates['index']) {
            $templates = $this->templates['index'];
        } elseif ($this->template) {
            $templates = $this->template;
        } else {
            // Build templates based on class hierarchy
            $actionTemplates = [];
            $classTemplates = [];
            $parentClass = static::class;
            while ($parentClass !== parent::class) {
                if ($action && $action != 'index') {
                    $actionTemplates[] = strtok($parentClass, '_') . '_' . $action;
                }
                $classTemplates[] = strtok($parentClass, '_');
                $parentClass = get_parent_class($parentClass);
            }

            $templates = array_unique(array_merge($actionTemplates, $classTemplates));
        }

        $templates[] = 'Page';
        return SSViewer::create($templates);
    }
}
