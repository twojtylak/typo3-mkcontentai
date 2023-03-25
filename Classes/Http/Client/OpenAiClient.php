<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Http\Client;

use Orhanerday\OpenAi\OpenAi;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Domain\Model\File;

class OpenAiClient extends BaseClient implements ClientInterface
{
    public function __construct()
    {
        $this->getApiKey();
    }

    public function image(string $text): \stdClass
    {
        $openAi = new OpenAi($this->getApiKey());

        $array = [
            'prompt' => $text,
            'n' => 1,
            'size' => '256x256',
        ];

        $response = $this->validateResponse($openAi->image($array));

        return $response;
    }

    public function createImageVariation(File $file): \stdClass
    {
        $openAi = new OpenAi($this->getApiKey());

        $array = [
            'image' => $file->getOriginalResource()->getContents(),
            'n' => 1,
            'size' => '256x256',
        ];

        $stream = curl_file_create(Environment::getPublicPath().$file->getOriginalResource()->getPublicUrl(), 'r');

        $array['image'] = $stream;

        $response = $this->validateResponse($openAi->createImageVariation($array));

        return $response;
    }

    public function validateApiCall(): \stdClass
    {
        $openAi = new OpenAi($this->getApiKey());

        $response = $this->validateResponse($openAi->listModels());

        return $response;
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    protected function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            throw new \Exception('Response is not string');
        }
        $response = json_decode($response);

        if ($response->error) {
            throw new \Exception($response->error->message);
        }

        return $response;
    }
}
