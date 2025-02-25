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

return [
    'blob_image' => [
        'path' => '/blob/image',
        'target' => DMK\MkContentAi\Controller\AjaxController::class.'::blobImage',
    ],
    'image_prompt' => [
        'path' => '/image/prompt',
        'target' => DMK\MkContentAi\Controller\AjaxController::class.'::promptResultAjaxAction',
    ],
    'alt_text' => [
        'path' => '/image/alt-text',
        'target' => DMK\MkContentAi\Controller\AjaxController::class.'::getAltText',
    ],
    'alt_texts' => [
        'path' => '/image/alt-texts',
        'target' => DMK\MkContentAi\Controller\AjaxController::class.'::getAltTexts',
    ],
    'alt_text_save' => [
        'path' => '/image/alt-text-save',
        'target' => DMK\MkContentAi\Controller\AjaxController::class.'::altTextSaveAction',
    ],
];
