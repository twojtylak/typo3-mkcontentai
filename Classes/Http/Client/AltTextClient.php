<?php

declare(strict_types=1);

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

use DMK\MkContentAi\Http\Client\Action\AltTextAction;
use DMK\MkContentAi\Service\SiteLanguageService;
use DMK\MkContentAi\Utility\AiUtility;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AltTextClient extends BaseClient implements ClientInterface
{
    private SiteLanguageService $siteLanguageService;

    private AltTextAction $altTextAction;

    private HttpClientInterface $client;

    public function __construct(SiteLanguageService $siteLanguageService, AltTextAction $altTextAction)
    {
        $this->siteLanguageService = $siteLanguageService;
        $this->altTextAction = $altTextAction;
        $this->client = HttpClient::create();
    }

    /**
     * Returns an array with authorization headers.
     *
     * @return array<string, string>
     */
    private function getAuthorizationHeader(): array
    {
        return [
            'X-API-Key' => $this->getApiKey(),
        ];
    }

    /**
     * @throws \Exception
     */
    public function getAltTextForFile(File $file, ?string $languageIsoCode = null): string
    {
        $localFile = $file->getOriginalResource()->getForLocalProcessing();

        if (null === $languageIsoCode) {
            $languageIsoCode = $this->siteLanguageService->getLanguage();
        }
        $formFields = [
            'image[raw]' => DataPart::fromPath($localFile),
            'image[asset_id]' => AiUtility::getAiAssetId($file->getOriginalResource()->getUid(), $languageIsoCode),
        ];

        if (null !== $languageIsoCode) {
            $formFields['lang'] = $languageIsoCode;
        } elseif (null !== $this->siteLanguageService->getLanguage()) {
            $formFields['lang'] = $this->siteLanguageService->getLanguage();
        }

        $formData = new FormDataPart($formFields);

        $headers = array_merge($this->getAuthorizationHeader(), $formData->getPreparedHeaders()->toArray());

        $response = $this->client->request('POST', $this->altTextAction->buildFullUrl($this->altTextAction::API_LINK, 'altText', []), [
            'headers' => $headers,
            'body' => $formData->bodyToIterable(),
        ]);
        $responseStatusCode = $response->getStatusCode();

        $response = $this->validateResponse($response->getContent());

        if (in_array($responseStatusCode, [403, 413])) {
            $messages = [];

            foreach ($response->errors as $error) {
                foreach ($error as $errorMessage) {
                    if (is_string($errorMessage)) {
                        $messages[] = $errorMessage;
                    }
                }
            }

            throw new \Exception(implode(PHP_EOL, $messages), $responseStatusCode);
        }

        return $response->alt_text;
    }

    public function getByAssetId(int $assetId, ?string $languageIsoCode = null): string
    {
        if (null === $languageIsoCode) {
            $languageIsoCode = $this->siteLanguageService->getLanguage();
        }

        $assetIdWithLangIsoCode = AiUtility::getAiAssetId($assetId, $languageIsoCode);

        $response = $this->client->request('GET', $this->altTextAction->buildFullUrl($this->altTextAction::API_LINK, 'altTextByAssetId', [$assetIdWithLangIsoCode]), [
            'headers' => $this->getAuthorizationHeader(),
        ]);

        $response = $this->validateResponse($response->getContent());

        return $response->alt_text;
    }

    public function getAccount(): \stdClass
    {
        $response = $this->client->request('GET', $this->altTextAction->buildFullUrl($this->altTextAction::API_LINK, 'account', []), [
            'headers' => $this->getAuthorizationHeader(),
        ]);

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

        return $response;
    }

    public function getTestApiCall(): \stdClass
    {
        $response = $this->getAccount();

        return $response;
    }
}
