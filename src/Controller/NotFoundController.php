<?php

namespace KittyShare\Controller;

use KittyShare\Controller\BaseController;
use KittyShare\Manager\{ConfigManager, TranslationManager};
use KittyShare\Translations\Language;
use KittyShare\Http\{Request, Response, TemplateResponse};
use Override;

final class NotFoundController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        return new TemplateResponse(
            template: "error/404",
            parameters: [
                'path' => $request->getUri(),
                'baseUrl' => ConfigManager::get()->baseUrl,
                'translationManager' => new TranslationManager(Language::EN), // TODO: Do not hardcode
            ],
            statusCode: 404,
        );
    }
}
