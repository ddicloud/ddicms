<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model admin\models\auth\AuthRoute */
/* @var $form yii\widgets\MyActiveForm */
?>

<div class="auth-route-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->textInput() ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pid')->textInput() ?>

    <?= $form->field($model, 'data')->textInput() ?>

    <?= $form->field($model, 'module_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <?= Html::submitButton('保存', ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
