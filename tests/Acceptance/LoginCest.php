<?php
/** WorldCup Journey | D | Browser check for the protected dashboard. */

declare(strict_types=1);

namespace app\tests\Acceptance;

use app\tests\Support\AcceptanceTester;
use yii\helpers\Url;

final class LoginCest
{
    public function guestIsRedirectedToAdminLogin(AcceptanceTester $I): void
    {
        $I->amOnPage(Url::toRoute('/admin-dashboard/index'));
        $I->see('管理员登录', 'h1');
        $I->seeElement('input[name="AdminLoginForm[password]"]');
    }
}
