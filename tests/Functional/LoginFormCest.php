<?php
/** WorldCup Journey | D | Administrator login route checks. */

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\FunctionalTester;

final class LoginFormCest
{
    public function legacyLoginOpensAdminForm(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
        $I->see('管理员登录', 'h1');
        $I->seeElement('input[name="AdminLoginForm[username]"]');
        $I->seeElement('input[name="AdminLoginForm[password]"]');
    }

    public function dashboardRequiresLogin(FunctionalTester $I): void
    {
        $I->amOnRoute('admin-dashboard/index');
        $I->see('管理员登录', 'h1');
    }
}
