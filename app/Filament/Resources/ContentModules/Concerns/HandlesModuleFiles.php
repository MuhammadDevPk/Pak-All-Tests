<?php

namespace App\Filament\Resources\ContentModules\Concerns;

trait HandlesModuleFiles
{
    /**
     * Hydrate the Repeater 'files' state from the model's 'content_meta' JSON.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $meta = $data['content_meta'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        }

        $files = [];
        if (! empty($meta['files']) && is_array($meta['files'])) {
            foreach ($meta['files'] as $f) {
                $url = $f['file_url'] ?? '';
                $isUpload = ($f['source_type'] ?? '') === 'upload' || str_starts_with($url, '/storage/');
                $filePath = $f['document_file'] ?? ($isUpload ? ltrim(str_replace('/storage/', '', $url), '/') : null);

                $files[] = [
                    'title' => $f['title'] ?? '',
                    'source_type' => $f['source_type'] ?? ($isUpload ? 'upload' : 'url'),
                    'document_file' => $filePath,
                    'file_url' => $url,
                    'file_type' => $f['file_type'] ?? 'PDF Document',
                    'file_size' => $f['file_size'] ?? '',
                ];
            }
        } elseif (! empty($meta['file_url'])) {
            $url = $meta['file_url'];
            $isUpload = str_starts_with($url, '/storage/');
            $filePath = $isUpload ? ltrim(str_replace('/storage/', '', $url), '/') : null;

            $files[] = [
                'title' => $data['title'] ?? 'Document',
                'source_type' => $isUpload ? 'upload' : 'url',
                'document_file' => $filePath,
                'file_url' => $url,
                'file_type' => $meta['file_type'] ?? 'PDF Document',
                'file_size' => $meta['file_size'] ?? '',
            ];
        }

        $data['files'] = $files;

        return $data;
    }

    /**
     * Synchronize uploaded file paths into 'content_meta.files' and 'content_meta.file_url'
     * before Eloquent updates or creates the ContentModule record.
     */
    protected function processFilesBeforeSave(array $data): array
    {
        $meta = $data['content_meta'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        }

        $files = $data['files'] ?? [];
        $cleanFiles = [];

        foreach ($files as $f) {
            $docFile = $f['document_file'] ?? null;
            if (is_array($docFile)) {
                $docFile = reset($docFile);
            }

            $sourceType = $f['source_type'] ?? 'upload';
            $fileUrl = $f['file_url'] ?? null;

            if ($sourceType === 'upload' && ! empty($docFile)) {
                $fileUrl = '/storage/'.ltrim((string) $docFile, '/');
            }

            if (! empty($fileUrl) || ! empty($f['title'])) {
                $cleanFiles[] = [
                    'title' => $f['title'] ?? 'Document',
                    'source_type' => $sourceType,
                    'document_file' => $docFile,
                    'file_url' => $fileUrl,
                    'file_type' => $f['file_type'] ?? 'PDF Document',
                    'file_size' => $f['file_size'] ?? null,
                ];
            }
        }

        $meta['files'] = $cleanFiles;

        // Ensure top-level file_url is updated with the active document
        if (count($cleanFiles) > 0 && ! empty($cleanFiles[0]['file_url'])) {
            $meta['file_url'] = $cleanFiles[0]['file_url'];
            $meta['file_type'] = $cleanFiles[0]['file_type'] ?? 'PDF Document';
            $meta['file_size'] = $cleanFiles[0]['file_size'] ?? null;
        }

        $data['content_meta'] = $meta;
        unset($data['files']);

        return $data;
    }
}
