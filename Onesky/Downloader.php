<?php

namespace Seven\Bundle\OneskyBundle\Onesky;

use Onesky\Api\Client;

class Downloader
{
    /** @var Client */
    private $client;

    /** @var int */
    private $project;

    /** @var Mapping[] */
    private $mappings = [];

    /** @var array $localeFormat */
    private $localeFormat = [];

    /**
     * @param Client $client
     * @param int    $project
     * @param array  $localeFormat
     */
    public function __construct(Client $client, $project, $localeFormat)
    {
        $this->client = $client;
        $this->project = $project;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @param Mapping $mapping
     *
     * @return $this
     */
    public function addMapping(Mapping $mapping)
    {
        $this->mappings[] = $mapping;

        return $this;
    }

    /**
     * @return $this
     */
    public function download()
    {
        foreach ($this->getAllSources() as $source) {
            foreach ($this->getAllLocales() as $locale) {
                $this->dump($source, $locale);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getAllLocales()
    {
        $raw = $this->client->projects('languages', ['project_id' => $this->project]);
        $response = json_decode($raw, true);
        $data = $response['data'];

        return array_map(function ($item) {
            return $this->formatLocale($item);
        }, $data);
    }

    /**
     * @param array $locale
     *
     * @return string
     */
    private function formatLocale(array $locale = array())
    {
        $intersect = array_intersect_key($locale, array_flip($this->localeFormat['parts']));

        return (count($intersect)) ? implode($this->localeFormat['separator'], $intersect) : null;
    }

    /**
     * @return array
     */
    private function getAllSources()
    {
        $raw = $this->client->files('list', ['project_id' => $this->project, 'per_page' => 100]);
        $response = json_decode($raw, true);
        $data = $response['data'];

        return array_map(function ($item) {
            return $item['file_name'];
        }, $data);
    }

    /**
     * @param string $source
     * @param string $locale
     *
     * @return $this
     */
    private function dump($source, $locale)
    {
        $content = null;

        foreach ($this->mappings as $mapping) {
            if (!$mapping->useLocale($locale) || !$mapping->useSource($source)) {
                continue;
            }

            if ($content === null) {
                $content = $this->fetch($source, $locale);
            }

            $this->write($mapping->getOutputFilename($source, $locale), $content);
        }

        return $this;
    }

    /**
     * @param string $source
     * @param string $locale
     *
     * @return mixed
     */
    private function fetch($source, $locale)
    {
        $content = $this->client->translations(
            'export',
            [
                'project_id' => $this->project,
                'locale' => $locale,
                'source_file_name' => $source,
            ]
        );

        return $content;
    }

    /**
     * @param $file
     * @param $content
     *
     * @return $this
     */
    private function write($file, $content)
    {
        $this->createFilePath($file);

        file_put_contents($file, $content);

        return $this;
    }

    /**
     * @param string $filename
     *
     * @throws \Exception
     */
    private function createFilePath($filename)
    {
        if (file_exists($filename)) {
            if (!is_writable($filename)) {
                throw new \Exception(sprintf('File path "%s" is not writable', $filename));
            }

            return;
        }

        $dir = dirname($filename);

        if (is_dir($dir)) {
            if (!is_writable($dir)) {
                throw new \Exception(sprintf('Directory "%s" is not writable', $dir));
            }

            return;
        }

        if (!mkdir($dir, 0777, true)) {
            throw new \Exception(sprintf('Unable to create directory "%s"', $dir));
        }
    }
}
