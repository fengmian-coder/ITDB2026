<?php
/** WorldCup Journey | D | Administrator sign-in page. */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = '管理员登录';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'username')->textInput(['autocomplete' => 'username'])->label('账号') ?>
<?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password'])->label('密码') ?>
<?= Html::submitButton('登录', ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>
