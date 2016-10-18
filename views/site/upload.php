<?php
use yii\widgets\ActiveForm;
?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

<?= $form->field($model, 'file')->fileInput() ?>
<?= $form->field($text, 'textinfo')->textInput(['maxlength' => true]) ?>

<button>Submit</button>

<?php ActiveForm::end() ?>