<?php
/**
 * WorldCup Journey - Web 根外课程文件存储、替换与路径边界检查。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、FileHelper、data/team 与 data/personal。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\services;

use app\models\ProjectDocument;
use RuntimeException;
use Throwable;
use Yii;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class DocumentStorageService
{
    public function resolve(ProjectDocument $document): string
    {
        $directory = $document->document_type === 'TEAM' ? 'team' : 'personal';
        $relative = (string) $document->relative_path;
        if (!preg_match('~\A' . $directory . '/[a-f0-9]{32}\.([a-z0-9]+)\z~', $relative, $match)
            || !in_array($match[1], ProjectDocument::EXTENSIONS, true)) {
            throw new NotFoundHttpException('文件不可用');
        }
        $root = realpath(Yii::getAlias('@app/data/' . $directory));
        $path = realpath(Yii::getAlias('@app/data/') . $relative);
        if ($root === false || $path === false || !is_file($path)
            || !str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR))) {
            throw new NotFoundHttpException('文件不存在或已移除');
        }
        return $path;
    }

    /** 上传文件只复制；CLI 导入时不会移动或删除个人作业原文件。 */
    public function save(ProjectDocument $document): bool
    {
        if (!$document->validate()) {
            return false;
        }
        $newPath = null;
        $oldPath = null;
        $old = $document->isNewRecord ? null : ProjectDocument::findOne($document->id);
        $replace = $document->uploadFile !== null
            || ($old !== null && $old->document_type !== $document->document_type);
        try {
            if ($old !== null && $replace) {
                try {
                    $oldPath = $this->resolve($old);
                } catch (NotFoundHttpException $exception) {
                    if ($document->uploadFile === null) {
                        $document->addError('uploadFile', '原文件已缺失，请重新上传');
                        return false;
                    }
                }
            }
            if ($replace) {
                $directory = $document->document_type === 'TEAM' ? 'team' : 'personal';
                $extension = $document->uploadFile?->extension ?? pathinfo((string) $old->relative_path, PATHINFO_EXTENSION);
                $relative = $directory . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
                FileHelper::createDirectory(Yii::getAlias('@app/data/' . $directory));
                $newPath = Yii::getAlias('@app/data/') . $relative;
                $saved = $document->uploadFile !== null
                    ? $document->uploadFile->saveAs($newPath, false)
                    : copy($oldPath, $newPath);
                if (!$saved) {
                    throw new RuntimeException('文件保存失败');
                }
                $document->relative_path = $relative;
                if ($document->uploadFile !== null) {
                    $name = basename(str_replace('\\', '/', $document->uploadFile->name));
                    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? 'document.' . $extension;
                    $document->original_name = mb_substr($name, 0, 255, 'UTF-8');
                }
                $document->file_size = filesize($newPath);
                $document->uploaded_at = gmdate('Y-m-d H:i:s');
            }
            if (!$document->save(false)) {
                throw new RuntimeException('文件记录保存失败');
            }
        } catch (Throwable $exception) {
            if ($newPath !== null && is_file($newPath)) {
                unlink($newPath);
            }
            Yii::error(['operation' => 'save-project-document', 'exception' => $exception], self::class);
            $document->addError('uploadFile', '保存失败，请确认文件及所属团队仍然有效后重试');
            return false;
        }
        if ($oldPath !== null && is_file($oldPath) && !unlink($oldPath)) {
            Yii::warning(['operation' => 'remove-replaced-document', 'document_id' => $document->id], self::class);
        }
        return true;
    }

    public function delete(ProjectDocument $document): void
    {
        try {
            $path = $this->resolve($document);
        } catch (NotFoundHttpException $exception) {
            $path = null;
        }
        $document->delete();
        if ($path !== null && is_file($path) && !unlink($path)) {
            Yii::warning(['operation' => 'remove-deleted-document', 'document_id' => $document->id], self::class);
        }
    }
}
