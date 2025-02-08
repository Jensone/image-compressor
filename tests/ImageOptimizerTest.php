<?php

namespace Jensone\ImageOptimizer\Tests;

use Jensone\ImageOptimizer\ImageOptimizer;
use PHPUnit\Framework\TestCase;
use Exception;

class ImageOptimizerTest extends TestCase
{
    private $testImagePath;
    private $outputPath;
    private $testImageUrl = 'https://raw.githubusercontent.com/jensone/image-optimizer/main/tests/test-image.jpg';

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        $this->testImagePath = __DIR__ . '/test-image.jpg';
        $this->outputPath = __DIR__ . '/optimized-test-image.jpg';

        if (!file_exists($this->testImagePath) || filesize($this->testImagePath) === 0) {
            $imageContent = @file_get_contents($this->testImageUrl);
            if ($imageContent === false) {
                throw new Exception("Impossible to download the test image");
            }
            file_put_contents($this->testImagePath, $imageContent);
        }

        if (!$this->isValidImage($this->testImagePath)) {
            throw new Exception("The test image is not a valid image");
        }
    }

    /**
     * Clean up the test environment after each test.
     */
    protected function tearDown(): void
    {
        // Delete the optimized image after each test
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    /**
     * Test compressing a local file.
     */
    private function isValidImage(string $path): bool
    {
        $imageInfo = @getimagesize($path);
        return $imageInfo !== false;
    }

    /**
     * Test compressing a local file.
     */
    public function testCompressFile(): void
    {
        $optimizer = new ImageOptimizer(['quality' => 85]);
        $originalSize = filesize($this->testImagePath);

        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);

        $this->assertFileExists($result);
        $this->assertTrue($this->isValidImage($result), "The optimized image is not a valid image");
        $this->assertGreaterThan(0, filesize($result));
        $this->assertLessThanOrEqual($originalSize, filesize($result));
    }

    /**
     * Test compressing an image from a URL.
     */
    public function testCompressFromUrl(): void
    {
        $optimizer = new ImageOptimizer(['quality' => 85]);

        $result = $optimizer->compressFromUrl($this->testImageUrl, $this->outputPath);

        $this->assertFileExists($result); // Verify that the optimized file exists
    }

    /**
     * Test handling errors for a non-existent file.
     */
    public function testCompressFileNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fichier introuvable');

        $optimizer = new ImageOptimizer();
        $optimizer->compressFile('non-existent-path.jpg');
    }

    /**
     * Test handling errors for an invalid URL.
     */
    public function testCompressInvalidUrl(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API error');

        $optimizer = new ImageOptimizer();
        $optimizer->compressFromUrl('https://invalid-url.com/image.jpg', $this->outputPath);
    }

    /**
     * Test custom quality settings.
     */
    public function testCustomQuality(): void
    {
        $optimizer = new ImageOptimizer(['quality' => 50]);

        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);

        $this->assertFileExists($result); // Verify that the optimized file exists
    }

    /**
     * Test custom timeout settings.
     */
    public function testCustomTimeout(): void
    {
        $optimizer = new ImageOptimizer(['timeout' => 10]);

        $result = $optimizer->compressFile($this->testImagePath, $this->outputPath);

        $this->assertFileExists($result); // Verify that the optimized file exists
    }
}
