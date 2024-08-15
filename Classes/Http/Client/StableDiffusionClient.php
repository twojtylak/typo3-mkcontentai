<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "mkcontentai" by DMK E-BUSINESS GmbH.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Http\Client;

use DMK\MkContentAi\Domain\Model\Image;
use DMK\MkContentAi\Http\Client\Action\StableDiffusionAction;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class StableDiffusionClient extends BaseClient implements ImageApiInterface
{
    private StableDiffusionAction $stableDiffusionAction;

    public function __construct()
    {
        $this->getApiKey();
    }

    public function injectStableDiffusionAction(StableDiffusionAction $stableDiffusionAction): void
    {
        $this->stableDiffusionAction = $stableDiffusionAction;
    }

    public function getTestApiCall(): \stdClass
    {
        $response = $this->request($this->stableDiffusionAction->getActions()['model_list'], [], $this->stableDiffusionAction::DREAMBOOTH_API_LINK);

        $response = $this->validateResponse($response->getContent());

        return $response;
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            $translatedMessage = LocalizationUtility::translate('labelResponseNotString', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $response = json_decode($response);

        if (isset($response->status) && 'processing' == $response->status) {
            while ('processing' == $response->status) {
                sleep(2);
                $response = $this->request('', [], $response->fetch_result)->getContent();
                if (!is_string($response)) {
                    $translatedMessage = LocalizationUtility::translate('labelResponseNotString', 'mkcontentai') ?? '';

                    throw new \Exception($translatedMessage);
                }
                $response = json_decode($response);
                sleep(2);
            }
        }

        if (!is_a($response, \stdClass::class)) {
            $response = $this->convertToStdClass($response);
        }

        if (!empty($response->status) && !in_array($response->status, ['ok', 'success'])) {
            $this->throwException($response);
        }

        return $response;
    }

    private function throwException(\stdClass $response): void
    {
        $message = $response->messege ?? $response->message ?? null;
        if (is_string($message ?? null)) {
            throw new \Exception($message.' - StableDiffusion API');
        }
        if (is_iterable($message ?? null)) {
            $errors = [];
            foreach ($message as $message) {
                $errors[] = $message[0];
            }
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * @param array<string> $array
     */
    private function convertToStdClass(array $array): \stdClass
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * @param array<string, float|int|string|null> $queryParams
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function request(string $endpoint, array $queryParams = [], string $apiLinkAdjust = ''): ResponseInterface
    {
        $apiLink = $this->stableDiffusionAction::API_LINK;
        if ('' != $apiLinkAdjust) {
            $apiLink = $apiLinkAdjust;
        }
        $client = HttpClient::create();

        $commonParams = [];
        $commonParams['key'] = $this->getApiKey();
        $commonParams = array_merge($commonParams, $queryParams);

        $response = $client->request(
            'POST',
            $apiLink.$endpoint,
            [
                'body' => $commonParams,
            ]
        );

        return $response;
    }

    public function createImageVariation(File $file): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = current($siteFinder->getAllSites());
        $imageUrl = $file->getOriginalResource()->getPublicUrl();
        if (false != $site) {
            $imageUrl = $site->getBase().$imageUrl;
            if ($this->getCurrentModel()) {
                return $this->dreamboothVariant($imageUrl);
            }

            return $this->stableDiffusionVariant($imageUrl);
        }
        $translatedMessage = LocalizationUtility::translate('labelErrorImageCantCreated', 'mkcontentai') ?? '';

        throw new \Exception($translatedMessage);
    }

    /**
     * @return array<Image>
     */
    private function stableDiffusionVariant(string $imageUrl): array
    {
        $params = [
            'samples' => 3,
            'height' => 1024,
            'width' => 768,
            'prompt' => 'similar',
            'init_image' => $imageUrl,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
        ];

        $response = $this->request($this->stableDiffusionAction->getActions()['img2img'], $params);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    /**
     * @return array<Image>
     */
    private function dreamboothVariant(string $imageUrl): array
    {
        $params = [
            'samples' => 3,
            'height' => 1024,
            'width' => 768,
            'prompt' => 'similar',
            'init_image' => $imageUrl,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
            'model_id' => $this->getCurrentModel(),
            'scheduler' => 'UniPCMultistepScheduler',
        ];

        $response = $this->request($this->stableDiffusionAction->getActions()['img2img'], $params, $this->stableDiffusionAction::DREAMBOOTH_API_LINK);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    public function image(string $text): array
    {
        if ($this->getCurrentModel()) {
            return $this->dreamboothImage($text);
        }

        return $this->stableDiffusionImage($text);
    }

    public function upscale(File $file): Image
    {
        $translatedMessage = LocalizationUtility::translate('labelErrorApiUpscaleStableDiffusion', 'mkcontentai') ?? '';

        throw new \Exception($translatedMessage);
    }

    /**
     * @return array<Image>
     */
    public function extend(string $sourceImage, string $text = '', ?string $promptText = ''): array
    {
        $translatedMessage = LocalizationUtility::translate('labelErrorNotImplemented', 'mkcontentai') ?? '';

        throw new \Exception($translatedMessage);
    }

    /**
     * @return array<Image>
     */
    private function dreamboothImage(string $text): array
    {
        $params = [
            'prompt' => $text,
            'samples' => 3,
            'width' => 1024,
            'height' => 768,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
            'model_id' => $this->getCurrentModel(),
        ];
        $response = $this->request('', $params, $this->stableDiffusionAction::DREAMBOOTH_API_LINK);

        return $this->generateImages($response);
    }

    /**
     * @return array<Image>
     */
    private function stableDiffusionImage(string $text): array
    {
        $params = [
            'prompt' => $text,
            'samples' => 3,
            'width' => 1024,
            'height' => 768,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
        ];

        $response = $this->request($this->stableDiffusionAction->getActions()['text2img'], $params);

        return $this->generateImages($response);
    }

    /**
     * @return array<Image>
     */
    private function responseToImages(\stdClass $response): array
    {
        $images = [];
        foreach ($response->output as $url) {
            $images[] = GeneralUtility::makeInstance(Image::class, $url);
        }

        return $images;
    }

    public function getFolderName(): string
    {
        return 'stablediffusion';
    }

    /**
     * @return array<string>
     */
    public function modelList(): array
    {
        if (empty($this->getApiKey())) {
            return [];
        }

        $response = $this->request($this->stableDiffusionAction->getActions()['model_list'], [], $this->stableDiffusionAction::DREAMBOOTH_API_LINK);

        $response = $this->validateResponse($response->getContent());

        if (is_string(json_encode($response))) {
            return json_decode(json_encode($response), true);
        }

        return [];
    }

    public function setCurrentModel(?string $modelName = null): void
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();
        $registry->set($class, 'modelName', $modelName);
    }

    public function getCurrentModel(): string
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return strval($registry->get($class, 'modelName'));
    }

    public function getAllowedOperations(): array
    {
        return ['variants', 'filelist', 'saveFile', 'promptResult', 'prompt', 'promptResultAjax'];
    }

    private function fetchImages(string $responseId): \stdClass
    {
        $response = $this->request($this->stableDiffusionAction->getActions()['fetch'].$responseId);

        $response = $this->validateResponse($response->getContent());

        return $response;
    }

    /**
     * @return Image[]
     */
    private function generateImages(ResponseInterface $response): array
    {
        $response = $this->validateResponse($response->getContent());

        $responseId = (string) $response->id;
        $timer = 0;

        $images = $this->responseToImages($response);

        do {
            $fetchResponse = $this->fetchImages($responseId);
            sleep(5);
            ++$timer;
        } while ('success' !== $fetchResponse->status && $timer <= 4);

        return $images;
    }
}
