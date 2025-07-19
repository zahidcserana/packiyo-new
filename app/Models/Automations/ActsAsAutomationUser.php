<?php

namespace App\Models\Automations;

use App\Models\Automation;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

trait ActsAsAutomationUser
{
    protected function actingAsAutomation(Closure $callback): void
    {
        if (Auth::hasUser()) {
            $authenticatedUser = Auth::user();
            Auth::setUser($this->getOrCreateAutomationUser());
        }

        call_user_func($callback);

        // Otherwise you can't really log the Co-Pilot user out.
        if (isset($authenticatedUser)) {
            Auth::setUser($authenticatedUser);
        }
    }

    private function getOrCreateAutomationUser(): User
    {
        $automationUserData = ['email' => Automation::AUTOMATION_USER_EMAIL];
        $automationUser = User::where($automationUserData)->first();

        if (!$automationUser) {
            $automationUser = User::create($automationUserData);
            $automationUser->contactInformation()->create([
                'name' => Automation::AUTOMATION_USER_NAME,
                'company' => Automation::AUTOMATION_USER_COMPANY
            ]);
        }

        if ($automationUser->tokens->isEmpty()) {
            $automationUser->createToken(Automation::AUTOMATION_USER_NAME);
        }

        return $automationUser;
    }
}
