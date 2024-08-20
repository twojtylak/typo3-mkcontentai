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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Controller\AiImageController;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AiImageService
{
    public function setAiImageEngine(int $imageAiEngine): void
    {
        if (0 === $imageAiEngine) {
            return;
        }

        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY, $imageAiEngine);
    }
}
