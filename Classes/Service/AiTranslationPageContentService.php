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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Domain\Repository\TtContentRepository;
use DMK\MkContentAi\Http\Client\SummAiClient;

class AiTranslationPageContentService
{
    public SummAiClient $summAiClient;
    public TtContentRepository $ttContentRepository;

    public function __construct(SummAiClient $summAiClient, TtContentRepository $ttContentRepository)
    {
        $this->summAiClient = $summAiClient;
        $this->ttContentRepository = $ttContentRepository;
    }

    public function getTranslation(string $inputText, string $userEmail, string $inputTextType, string $targetLanguageType, string $separator): \stdClass
    {
        return $this->summAiClient->sendContentToTranslate($inputText, $userEmail, $inputTextType, $targetLanguageType, $separator);
    }

    public function getSummAiUserEmail(): string
    {
        return $this->summAiClient->getUserEmail();
    }

    public function getRecordToTranslate(int $recordUid): ?TtContent
    {
        return $this->ttContentRepository->findByUid($recordUid);
    }
}
