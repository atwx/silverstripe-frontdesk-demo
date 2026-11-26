<?php

namespace App\Controllers;

use App\Demo\DemoAccount;
use Atwx\SilverstripeFrontdeskKit\Controller\ProfileController;
use SilverStripe\Security\Security;

/**
 * The kit's profile page, closed for the shared demo account.
 *
 */
class DemoProfileController extends ProfileController
{
    public function init()
    {
        parent::init();
        if (DemoAccount::isDemoAccount(Security::getCurrentUser())) {
            $this->httpError(403, 'The shared demo account cannot be changed.');
        }
    }
}
