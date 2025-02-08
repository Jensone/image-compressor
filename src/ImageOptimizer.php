<?php

namespace Jensone\ImageOptimizer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

/**
 * Class ImageOptimizer
 * @package Jensone\ImageOptimizer
 * @author  Jensone <hello@jensone.com>
 * @license MIT
 * 
 * ImageOptimizer is a library to compress images via the API resmush.it
 * README : https://github.com/jensone/image-compressor
 * Documentation : https://api.resmush.it/
 */
class ImageOptimizer
{
    private Client $client;
    private array $defaultOptions;

    public function __construct(array $options = [])
    {
        $this->defaultOptions = array_merge([
            'quality' => 92,
            'timeout' => 30
        ], $options);

        $this->client = new Client([
            'base_uri' => 'https://api.resmush.it/',
            'timeout' => $this->defaultOptions['timeout']
        ]);
    }

    /**
     * Compress a local image and save it in the specified path
     * @param string $filePath
     * @param string $outputPath
     * @return string
     */
    public function compressFile(string $filePath, string $outputPath = null): string
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found : $filePath");
        }
    
        if (!is_readable($filePath)) {
            throw new Exception("File not readable : $filePath");
        }
    
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            throw new Exception("File is empty : $filePath");
        }

        try {
            // Call API to compress the image
            $response = $this->client->post('', [
                'multipart' => [
                    [
                        'name' => 'files',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath)
                    ],
                    [
                        'name' => 'qlty',
                        'contents' => $this->defaultOptions['quality']
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['error'])) {
                throw new Exception("API error: " . $result['error']);
            }

            // Download the optimized image
            $optimizedContent = file_get_contents($result['dest']);

            // Save the optimized image in the specified path
            $outputPath = $outputPath ?: dirname($filePath) . '/optimized-' . basename($filePath);
            file_put_contents($outputPath, $optimizedContent);

            return $outputPath;

        } catch (GuzzleException $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }
    }

    /**
     * Compress an image from a URL and save it in the specified path
     * @param string $imageUrl
     * @param string $outputPath
     * @return string
     */
    public function compressFromUrl(string $imageUrl, string $outputPath): string
    {
        try {
            // Call API to compress the image
            $response = $this->client->post('', [
                'form_params' => [
                    'img' => $imageUrl,
                    'qlty' => $this->defaultOptions['quality']
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['error'])) {
                throw new Exception("API error: " . $result['error']);
            }

            $optimizedContent = file_get_contents($result['dest']);
            file_put_contents($outputPath, $optimizedContent);

            return $outputPath;

        } catch (GuzzleException $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }
    }
}
