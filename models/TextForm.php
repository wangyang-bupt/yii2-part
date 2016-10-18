<?php

namespace app\models;

use yii\base\Model;

/**
 * UploadForm is the model behind the upload form.
 */
class TextForm extends Model
{
    /**
     * @var UploadedFile file attribute
     */
    public $textinfo;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['textinfo'], 'required']
        ];
    }
}

?>