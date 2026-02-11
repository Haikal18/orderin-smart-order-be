<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

class ImageKitService
{
    protected $imageKit;

    public function __construct()
    {
        $handlerStack = HandlerStack::create(new CurlHandler([
            'verify' => false, 
        ]));
        
        $handlerStack->push(function ($handler) {
            return function ($request, $options) use ($handler) {
                $options['verify'] = false;
                $options['curl'] = [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ];
                return $handler($request, $options);
            };
        });
        
        $this->imageKit = new ImageKit(
            env('IMAGEKIT_PUBLIC_KEY'),
            env('IMAGEKIT_PRIVATE_KEY'),
            env('IMAGEKIT_URL_ENDPOINT'),
            'path', 
            $handlerStack
        );
    }

    public function upload(UploadedFile $file, string $folder = 'foods'): ?array
    {
        try {
            $base64File = base64_encode(file_get_contents($file->getRealPath()));
            
            $uploadParams = [
                'file' => $base64File,
                'fileName' => time() . '_' . $file->getClientOriginalName(),
                'folder' => $folder,
                'useUniqueFileName' => true,
            ];

            $uploadFile = $this->imageKit->uploadFile($uploadParams);

            if ($uploadFile->error === null && isset($uploadFile->result)) {
                return [
                    'url' => $uploadFile->result->url,
                    'fileId' => $uploadFile->result->fileId,
                ];
            }

            Log::error('ImageKit upload failed', [
                'error' => $uploadFile->error,
                'fileName' => $file->getClientOriginalName()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ImageKit upload exception: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'exception' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Delete file from ImageKit
     *
     * @param string $fileId
     * @return bool
     */
    public function delete(string $fileId): bool
    {
        try {
            $deleteFile = $this->imageKit->deleteFile($fileId);
            
            return $deleteFile->error === null;
        } catch (\Exception $e) {
            Log::error('ImageKit delete exception: ' . $e->getMessage(), [
                'fileId' => $fileId
            ]);
            
            return false;
        }
    }

    /**
     * Generate URL for uploaded image
     *
     * @param string $path
     * @param array $transformations
     * @return string
     */
    public function getUrl(string $path, array $transformations = []): string
    {
        try {
            return $this->imageKit->url([
                'path' => $path,
                'transformation' => $transformations,
            ]);
        } catch (\Exception $e) {
            Log::error('ImageKit getUrl exception: ' . $e->getMessage());
            return '';
        }
    }
}
