<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\SeniorCitizenDocument;
use App\Services\SeniorCitizens\IdentityDocuments;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IdentityDocumentsTest extends TestCase
{
    public function test_s3_stores_a_compressed_private_object_and_previews_the_original(): void
    {
        config([
            'uploads.senior_document_disk' => 's3',
            'uploads.senior_document_bucket' => 'identity-documents',
            'filesystems.disks.s3.key' => 'synthetic',
            'filesystems.disks.s3.secret' => 'synthetic',
            'filesystems.disks.s3.region' => 'us-east-1',
            'filesystems.disks.s3.endpoint' => 'https://storage.example.test/storage/v1/s3',
            'filesystems.disks.s3.use_path_style_endpoint' => true,
        ]);
        $handler = new MockHandler([new Result]);
        $this->app->bind(S3Client::class, fn ($app, array $parameters) => new S3Client($parameters['args'] + ['handler' => $handler]));
        $storage = $this->app->make(IdentityDocuments::class);
        $source = "%PDF-1.4\n".str_repeat('Synthetic content. ', 30);

        $document = new SeniorCitizenDocument($storage->store(UploadedFile::fake()->createWithContent('proof.pdf', $source)));

        $this->assertSame('s3', $document->storage_disk);
        $this->assertSame('PutObject', $handler->getLastCommand()->getName());
        $request = $handler->getLastRequest();
        $this->assertSame('/storage/v1/s3/identity-documents/'.$document->path, $request->getUri()->getPath());
        $this->assertFalse($request->hasHeader('x-amz-acl'));
        $this->assertSame('application/gzip', $request->getHeaderLine('Content-Type'));
        $archive = (string) $request->getBody();
        $this->assertLessThan(strlen($source), strlen($archive));

        $handler->append(new Result(['Body' => Utils::streamFor($archive)]));
        config(['uploads.senior_document_disk' => 'local']);

        $preview = TestResponse::fromBaseResponse($storage->preview($document));

        $this->assertSame('GetObject', $handler->getLastCommand()->getName());
        $preview->assertHeader('Content-Disposition', 'inline; filename=supporting-document.pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($source, $preview->streamedContent());
    }
}
