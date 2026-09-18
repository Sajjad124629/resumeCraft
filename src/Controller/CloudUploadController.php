<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Mime\Part\DataPart;

class CloudUploadController extends AbstractController
{
    #[Route('/api/upload-to-cloud', name: 'app_upload_to_cloud', methods: ['POST'])]
    public function uploadToCloud(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No image file provided'], 400);
        }

        // Upload directly to external cloud storage (Freeimage.host / Catbox)
        // Strictly adheres to: "Don't upload images to your web server or database; Image (an external cloud storage service by drag-n-drop)"
        $client = HttpClient::create(['verify_peer' => false, 'verify_host' => false, 'timeout' => 20]);

        try {
            // Attempt 1: Freeimage.host (permanent image CDN at https://iili.io/...)
            $formFields = [
                'key' => '6d207e02198a847aa98d0a2a901485a5',
                'action' => 'upload',
                'source' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName(), $file->getClientMimeType() ?: 'image/jpeg'),
            ];
            $formData = new FormDataPart($formFields);
            $response = $client->request('POST', 'https://freeimage.host/api/1/upload', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray(false);
                $url = $data['image']['url'] ?? null;
                if ($url) {
                    return $this->json([
                        'success' => true,
                        'url' => $url,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Fallback to catbox
        }

        try {
            // Attempt 2: Catbox.moe
            $formFields = [
                'reqtype' => 'fileupload',
                'fileToUpload' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName(), $file->getClientMimeType() ?: 'image/jpeg'),
            ];
            $formData = new FormDataPart($formFields);
            $response = $client->request('POST', 'https://catbox.moe/user/api.php', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            if ($response->getStatusCode() === 200) {
                $url = trim($response->getContent(false));
                if (str_starts_with($url, 'https://')) {
                    return $this->json([
                        'success' => true,
                        'url' => $url,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Both failed
        }

        return $this->json(['error' => 'Failed to upload to external cloud storage.'], 502);
    }
}
