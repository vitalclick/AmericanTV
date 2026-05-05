<?php

namespace App\Traits;

use App\Constants\Status;
use App\Models\Storage;
use Aws\S3\MultipartUploader;
use Exception;
use Illuminate\Support\Facades\File;

trait StorageDriver
{
    protected function uploadServer($fileName, $path, $video, $folder)
    {

        $sizeInBytes = filesize($path);
        $sizeInMB    = $sizeInBytes / 1048576;

        if (@$video->storage) {
            $storage = @$video->storage;
        } else {
            $storage = $this->getNextStorage($sizeInMB);
        }

        if (!$storage) {
            return false;
        }

        $video->storage_id = $storage->id;
        $video->save();

        if (in_array($storage->type, [Status::WASABI_SERVER, Status::DIGITAL_OCEAN_SERVER])) {
            try {
 
                $s3Client = s3Client($storage);
                $bucket = $storage->config?->bucket;
                $key = ltrim($folder . '/' . $fileName, '/');
                $uploader = new MultipartUploader($s3Client, $path, [
                    'bucket' => $bucket,
                    'key'    => $key,
                    'ACL'    => 'public-read',
                ]);

                $uploader->upload();

                $storage->available_space -= $sizeInMB;
                $storage->save();
                File::DELETE($path);
                return true;
            } catch (\Throwable $th) {
                return false;
            }
        } else {
            try {

                $ftp         = ftp($storage);
                $folder      = dirname($fileName);
                $folderParts = explode('/', $folder);

                if (!@ftp_chdir($ftp['ftpConn'], $ftp['ftpRoot'] . '/' . $folder)) {
                    $pathToCreate = $ftp['ftpRoot'];

                    foreach ($folderParts as $part) {
                        if (!empty($part)) {
                            $pathToCreate .= '/' . $part;

                            if (!@ftp_chdir($ftp['ftpConn'], $pathToCreate)) {
                                if (!@ftp_mkdir($ftp['ftpConn'], $pathToCreate)) {
                                    throw new Exception("Failed to create directory: " . $pathToCreate);
                                }
                            }
                        }
                    }
                }

                $remoteFilePath = $ftp['ftpRoot'] . '/' . $fileName;
                ftp_put($ftp['ftpConn'], $remoteFilePath, $path, FTP_BINARY);
                ftp_close($ftp['ftpConn']);

                $storage->available_space -= $sizeInMB;
                $storage->save();
                File::delete($path);

                return true;
            } catch (\Throwable $e) {

                return false;
            }
        }
    }

    protected function removeOldFile($uploadVideo, $storage, $fileName, $folder)
    {
        if (in_array($storage->type, [Status::WASABI_SERVER, Status::DIGITAL_OCEAN_SERVER])) {

            s3Client($storage)->deleteObject([
                'Bucket' => @$storage->config?->bucket,
                'Key'    => $folder . '/' . $fileName,
            ]);

        } else if ($storage->type == Status::FTP_SERVER) {
            $ftp = ftp($storage);
            $ftpConn = $ftp['ftpConn'];
            $filePath = rtrim($ftp['ftpRoot']) . '/' . $fileName;
            $fileList = ftp_nlist($ftpConn, dirname($filePath));

            if ($fileList && in_array($filePath, $fileList)) {
                ftp_delete($ftp['ftpConn'], rtrim($ftp['ftpRoot']) . '/' . $fileName);
            }

            ftp_close($ftp['ftpConn']);
        }
        $uploadVideo->storage_id = 0;
        $uploadVideo->save();
    }

    public function getNextStorage($sizeInMB)
    {
        $storages      = Storage::active()->get();
        $totalStorages = count($storages);

        if ($totalStorages == 0) {
            return false;
        }

        $gs = gs();

        $lastUsedStorageId = $gs->storage_used_id;
        $nextStorage       = null;

        $lastIndex = $storages->search(fn($storage) => $storage->id == $lastUsedStorageId);

        for ($i = 1; $i <= $totalStorages; $i++) {
            $nextIndex        = ($lastIndex + $i) % $totalStorages;
            $candidateStorage = $storages[$nextIndex];

            if ($candidateStorage->available_space >= $sizeInMB) {
                $nextStorage = $candidateStorage;
                break;
            }
        }

        if (!$nextStorage) {
            return false;
        }

        $gs->storage_used_id = $nextStorage->id;
        $gs->save();

        return $nextStorage;
    }
}
