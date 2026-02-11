<?php

declare(strict_types=1);

namespace Application\Handler\Donor;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\FinishIDCheck;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IdentityCheckPassedHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(FinishIDCheck::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $this->opgApiService->updateCaseAssistance(
                $uuid,
                $form->get('assistance')->getValue(),
                $form->get('details')->getValue()
            );

            $siriusEditUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

            return new RedirectResponse($siriusEditUrl);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/identity_check_passed',
            [
                'details_data' => $detailsData,
                'form' => $form,
            ]
        ));
    }
}
