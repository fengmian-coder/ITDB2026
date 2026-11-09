<?php
/** WorldCup Journey | D | Public submissions and moderation state. */

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class GuestbookMessage extends ActiveRecord
{
    public const PENDING = 'PENDING';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';

    public static function tableName(): string
    {
        return 'guestbook_message';
    }

    public function rules(): array
    {
        return [
            [['nickname', 'content'], 'trim'],
            [['nickname', 'content'], 'required'],
            ['nickname', 'string', 'max' => 50],
            ['content', 'string', 'max' => 2000],
            ['related_match_id', 'default', 'value' => null],
            ['related_match_id', 'integer', 'min' => 1],
            ['related_match_id', 'exist', 'targetClass' => WcMatch::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getReply(): ActiveQuery
    {
        return $this->hasOne(GuestbookReply::class, ['message_id' => 'id']);
    }
}
