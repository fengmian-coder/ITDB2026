<?php
/**
 * WorldCup Journey - 管理员登录表单，沿用 D 的字段与验证流程。
 * 模块：D 认证 / A 登录界面；负责人：D、A。
 * 依赖：AdminLoginForm、ActiveForm；最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = '管理员登录';
?>
<div class="auth-login">
    <h1>管理员登录</h1>
    <p class="auth-form-description">输入管理员账号和密码</p>
    <?php $form = ActiveForm::begin(['id' => 'admin-login-form']); ?>
        <?= $form->errorSummary($model, ['header' => '登录未成功，请检查以下信息：']) ?>
        <?= $form->field($model, 'username')->textInput(['autocomplete' => 'username', 'autofocus' => true, 'placeholder' => '请输入管理员账号', 'required' => true])->label('管理员账号') ?>
        <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password', 'placeholder' => '请输入登录密码', 'required' => true])->label('登录密码') ?>
        <?= Html::submitButton('登录工作台 →', ['class' => 'btn btn-primary auth-submit']) ?>
    <?php ActiveForm::end(); ?>
    <p class="auth-login-note">此入口用于网站内容管理。浏览比赛、制作纪念卡和留言无需管理员账号</p>
</div>
