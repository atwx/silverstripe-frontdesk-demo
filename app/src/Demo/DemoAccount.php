<?php

namespace App\Demo;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Protects the shared demo account: visitors cannot change its name, email
 * or password, and failed logins never lock it. Only the seeder may change
 * it (resetting()). Also provides the demo login for the templates.
 *
 * @property \SilverStripe\Security\Member|\App\Demo\DemoAccount $owner
 * @extends \Extension<\Member>
 */
class DemoAccount extends Extension implements TemplateGlobalProvider
{
    protected static bool $isResetting = false;

    /**
     * Runs $callback with the protection switched off (seeding, nightly reset).
     */
    public static function resetting(callable $callback): void
    {
        self::$isResetting = true;
        try {
            $callback();
        } finally {
            self::$isResetting = false;
        }
    }

    public static function isDemoAccount(?Member $member): bool
    {
        return $member && $member->Email === DemoSeeder::DEMO_EMAIL;
    }

    protected function onBeforeWrite(): void
    {
        $member = $this->getOwner();
        $wasDemo = $member->isChanged('Email') ? $member->getChangedFields()['Email']['before'] ?? '' : $member->Email;
        if (self::$isResetting || ($wasDemo !== DemoSeeder::DEMO_EMAIL && !self::isDemoAccount($member))) {
            return;
        }
        if (!$member->isInDB()) {
            return;
        }
        foreach (['Email', 'FirstName', 'Surname', 'Password'] as $field) {
            if ($member->isChanged($field, Member::CHANGE_VALUE)) {
                throw ValidationException::create('The shared demo account cannot be changed.');
            }
        }
        // Wrong passwords of other visitors must not lock everybody out.
        $member->LockedOutUntil = null;
        $member->FailedLoginCount = 0;
    }

    protected function canDelete(?Member $member): ?bool
    {
        return self::isDemoAccount($this->getOwner()) && !self::$isResetting ? false : null;
    }

    public static function DemoIsLoginPage(): bool
    {
        $controller = Controller::curr();
        if (!$controller) {
            return false;
        }
        $url = strtolower(trim((string) $controller->getRequest()->getURL(), '/'));
        return $url === 'security/login' || str_starts_with($url, 'security/login/');
    }

    /**
     * @return array<string, array<string, string>|string>
     */
    public static function get_template_global_variables(): array
    {
        return [
            'DemoLoginEmail' => ['method' => 'demoEmail', 'casting' => 'Text'],
            'DemoLoginPassword' => ['method' => 'demoPassword', 'casting' => 'Text'],
            'DemoIsLoginPage' => 'DemoIsLoginPage',
        ];
    }

    public static function demoEmail(): string
    {
        return DemoSeeder::DEMO_EMAIL;
    }

    public static function demoPassword(): string
    {
        return DemoSeeder::DEMO_PASSWORD;
    }
}
