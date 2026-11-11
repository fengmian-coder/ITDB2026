<?php
/**
 * WorldCup Journey - 团队与个人课程作业元数据、上传及归属校验。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectTeam、ProjectMember、UploadedFile、project_document。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

class ProjectDocument extends ActiveRecord
{
    public const TYPES = ['TEAM' => '团队作业', 'PERSONAL' => '个人作业'];
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rp', 'txt', 'md', 'sql', 'png', 'jpg', 'jpeg', 'webp'];
    public ?UploadedFile $uploadFile = null;

    public static function tableName(): string
    {
        return 'project_document';
    }

    public function rules(): array
    {
        return [
            ['title', 'trim'],
            [['project_team_id', 'document_type', 'title'], 'required'],
            ['title', 'string', 'max' => 160],
            ['project_team_id', 'integer', 'min' => 1],
            ['project_team_id', 'exist', 'targetClass' => ProjectTeam::class, 'targetAttribute' => 'id'],
            ['document_type', 'in', 'range' => array_keys(self::TYPES)],
            ['member_id', 'default', 'value' => null],
            ['member_id', 'integer', 'min' => 1],
            ['member_id', 'validateOwner', 'skipOnEmpty' => false],
            ['uploadFile', 'file', 'extensions' => self::EXTENSIONS,
                // OOXML、RP 和 ZIP 的 MIME 可能相同；另以 MIME 白名单及附件下载限制保护。
                'checkExtensionByMimeType' => false,
                'mimeTypes' => [
                    'application/pdf', 'application/zip', 'application/x-zip', 'application/x-zip-compressed',
                    'application/octet-stream', 'application/msword', 'application/vnd.ms-excel',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.*',
                    'text/plain', 'text/markdown', 'text/x-sql', 'application/sql',
                    'image/png', 'image/jpeg', 'image/webp',
                ],
                'minSize' => 1, 'maxSize' => 20 * 1024 * 1024, 'skipOnEmpty' => false,
                'when' => static fn (self $model): bool => $model->isNewRecord || $model->uploadFile !== null],
        ];
    }

    public function validateOwner(string $attribute): void
    {
        if ($this->hasErrors('document_type') || $this->hasErrors('project_team_id') || $this->hasErrors($attribute)) {
            return;
        }
        if ($this->document_type === 'TEAM') {
            if ($this->member_id !== null) {
                $this->addError($attribute, '团队作业不应绑定个人成员');
            }
            return;
        }
        if ($this->member_id === null || !ProjectMember::find()->where([
            'id' => $this->member_id, 'project_team_id' => $this->project_team_id,
        ])->exists()) {
            $this->addError($attribute, '请选择所属团队中的一位成员');
        }
    }

    public function attributeLabels(): array
    {
        return ['project_team_id' => '所属团队', 'member_id' => '所属成员',
            'document_type' => '作业类型', 'title' => '作业标题', 'uploadFile' => '作业文件'];
    }

    public function getProjectTeam(): ActiveQuery
    {
        return $this->hasOne(ProjectTeam::class, ['id' => 'project_team_id']);
    }

    public function getMember(): ActiveQuery
    {
        return $this->hasOne(ProjectMember::class, ['id' => 'member_id']);
    }
}
