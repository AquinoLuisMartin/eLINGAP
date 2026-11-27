<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizenDocument;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class IdentityDocuments
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_ARCHIVE_BYTES = self::MAX_BYTES + 64 * 1024;

    public const PREVIEW_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(UploadedFile $file): array
    {
        try {
            $mime = $file->getMimeType();
            $size = $file->getSize();
            if (! isset(self::PREVIEW_EXTENSIONS[$mime]) || $size === false || $size < 1 || $size > self::MAX_BYTES) {
                throw new RuntimeException('Invalid identity document.');
            }

            $source = $file instanceof TemporaryUploadedFile ? $file->get() : $file->getContent();
            $originalName = mb_substr($file->getClientOriginalName(), 0, 255);
        } catch (Throwable) {
            throw new RuntimeException('Could not read the identity document.');
        }
        if (! is_string($source) || $source === '' || strlen($source) > self::MAX_BYTES) {
            throw new RuntimeException('Could not read the identity document.');
        }

        $contents = gzencode($source, 6);
        if ($contents === false) {
            throw new RuntimeException('Could not compress the identity document.');
        }

        $disk = config('uploads.senior_document_disk');
        $path = 'senior-documents/identity/'.Str::uuid().'.gz';

        try {
            $written = match ($disk) {
                'local' => Storage::disk('local')->put($path, $contents),
                's3' => $this->s3Request('PutObject', $path, ['Body' => $contents, 'ContentType' => 'application/gzip']),
                default => false,
            };
        } catch (Throwable) {
            throw new RuntimeException('Could not store the identity document.');
        }
        if ($written === false) {
            throw new RuntimeException('Could not store the identity document.');
        }

        return [
            'path' => $path,
            'storage_disk' => $disk,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size' => strlen($source),
            'stored_size' => strlen($contents),
        ];
    }

    public function preview(SeniorCitizenDocument $document): StreamedResponse
    {
        $archive = match ($document->storage_disk) {
            'local' => Storage::disk('local')->get($document->path),
            's3' => $this->readFromS3($document->path),
            default => throw new InvalidArgumentException('Invalid senior document disk.'),
        };

        abort_if($archive === null, 404);
        if (strlen($archive) !== (int) $document->stored_size || strlen($archive) > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('Invalid identity document archive.');
        }

        $contents = @gzdecode($archive, self::MAX_BYTES);
        if ($contents === false || strlen($contents) !== (int) $document->size) {
            throw new RuntimeException('Could not decompress the identity document.');
        }

        $extension = self::PREVIEW_EXTENSIONS[$document->mime_type];
        $response = response()->streamDownload(static function () use ($contents): void {
            echo $contents;
        }, 'supporting-document.'.$extension, [
            'Content-Type' => $document->mime_type,
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
        ], disposition: 'inline');
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function delete(string $disk, string $path): void
    {
        $deleted = match ($disk) {
            'local' => Storage::disk('local')->delete($path),
            's3' => $this->s3Request('DeleteObject', $path),
            default => throw new InvalidArgumentException('Invalid senior document disk.'),
        };
        if ($deleted === false) {
            throw new RuntimeException('Could not delete the identity document.');
        }
    }

    private function readFromS3(string $path): ?string
    {
        $result = $this->s3Request('GetObject', $path);
        if ($result === null) {
            return null;
        }
        $body = $result['Body'];

        try {
            return Utils::copyToString($body, self::MAX_ARCHIVE_BYTES + 1);
        } finally {
            $body->close();
        }
    }

    private function s3Request(string $operation, string $path, array $options = []): ?ResultInterface
    {
        $bucket = config('uploads.senior_document_bucket');
        if (blank($bucket)) {
            throw new InvalidArgumentException('The senior document bucket is required.');
        }

        try {
            $client = $this->s3();

            return $client->execute($client->getCommand($operation, ['Bucket' => $bucket, 'Key' => $path] + $options));
        } catch (Throwable $exception) {
            if ($operation === 'GetObject' && $exception instanceof AwsException
                && ($exception->getAwsErrorCode() === 'NoSuchKey' || ($exception->getStatusCode() === 404 && $exception->getAwsErrorCode() !== 'NoSuchBucket'))) {
                return null;
            }

            throw new RuntimeException("Supabase S3 {$operation} failed for the senior document.");
        }
    }

    private function s3(): S3Client
    {
        $disk = config('filesystems.disks.s3');

        return app(S3Client::class, ['args' => [
            'region' => $disk['region'],
            'endpoint' => $disk['endpoint'],
            'credentials' => ['key' => $disk['key'], 'secret' => $disk['secret']],
            'use_path_style_endpoint' => $disk['use_path_style_endpoint'],
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 3, 'timeout' => 10],
        ]]);
    }
}
