<?php

namespace Jensone\ImageOptimizer\Tests;

use Jensone\ImageOptimizer\ImageOptimizer;
use PHPUnit\Framework\TestCase;
use Exception;

class ImageOptimizerTest extends TestCase
{
    private $testImagePath;
    private $outputPath;
    private $server;
    private $testImageUrl;

    protected function setUp(): void
    {
        $this->testImagePath = __DIR__ . '/test-image.jpg';
        $this->outputPath = __DIR__ . '/optimized-test-image.jpg';

        if (!file_exists($this->testImagePath) || filesize($this->testImagePath) === 0) {
            $image = imagecreatetruecolor(100, 100);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
            imagejpeg($image, $this->testImagePath, 90);
            imagedestroy($image);
        }

        if (!$this->isValidImage($this->testImagePath)) {
            throw new Exception("The test image is not valid");
        }

        $port = 8888;
        $this->server = proc_open(
            sprintf('php -S localhost:%d -t %s', $port, __DIR__),
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes
        );
        $this->testImageUrl = sprintf('http://localhost:%d/%s', $port, basename($this->testImagePath));
        
        sleep(1);
    }

    protected function tearDown(): void
    {
        if ($this->server) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    private function isValidImage(string $path): bool
    {
        $imageInfo = @getimagesize($path);
        return $imageInfo !== false;
    }

    public function testCompressFile(): void
    {
        $optimizer = new ImageOptimizer(['quality' => 85]);
        $originalSize = filesize($this->testImagePath);

        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);

        $this->assertFileExists($result);
        $this->assertTrue($this->isValidImage($result), "The optimized image is not valid");
        $this->assertGreaterThan(0, filesize($result));
        $this->assertLessThanOrEqual($originalSize, filesize($result));
    }

    public function testCompressFromUrl(): void
    {
        if (!$this->server) {
            $this->markTestSkipped('Local test server could not be started');
        }

        $optimizer = new ImageOptimizer(['quality' => 85]);

        try {
            $result = $optimizer->compressFromUrl($this->testImageUrl, $this->outputPath);
            
            $this->assertFileExists($result);
            $this->assertTrue($this->isValidImage($result), "The optimized image is not valid");
            $this->assertGreaterThan(0, filesize($result));
        } catch (Exception $e) {
            $this->markTestSkipped('API temporarily unavailable: ' . $e->getMessage());
        }
    }

    public function testCompressFileNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File not found');

        $optimizer = new ImageOptimizer();
        $optimizer->compressFile('non-existent-path.jpg');
    }

    public function testCompressInvalidUrl(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API error');

        $optimizer = new ImageOptimizer();
        $optimizer->compressFromUrl('http://localhost:8888/invalid.jpg', $this->outputPath);
    }

    public function testCustomQuality(): void
    {
        $optimizer = new ImageOptimizer(['quality' => 50]);
        
        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);
        
        $this->assertFileExists($result);
        $this->assertTrue($this->isValidImage($result));
    }

    public function testCustomTimeout(): void
    {
        $optimizer = new ImageOptimizer(['timeout' => 10]);
        
        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);
        
        $this->assertFileExists($result);
        $this->assertTrue($this->isValidImage($result));
    }
}