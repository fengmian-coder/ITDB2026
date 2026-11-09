<?php
/** WorldCup Journey | D | One administrator reply per message. */

namespace app\models;

use yii\db\ActiveRecord;

class GuestbookReply extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'guestbook_reply';
    }

    public function rules(): array
    {
        return [
            ['content', 'trim'],
            ['content', 'required'],
            ['content', 'string', 'max' => 2000],
        ];
    }
}
