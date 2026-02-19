<?php

declare(strict_types=1);

namespace Application\Handler\Donor;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ChooseVouching;
use Application\Helpers\RouteHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhatIsVouchingHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(ChooseVouching::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            if ($form->get('chooseVouching')->getValue() === 'yes') {
                $this->opgApiService->sendIdentityCheck($uuid);
                $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $request);

                return $this->routeHelper->toRedirect("vouching_what_happens_next", ['uuid' => $uuid]);
            } else {
                return $this->routeHelper->toRedirect("how_will_you_confirm", ['uuid' => $uuid]);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/what_is_vouching',
            [
                'details_data' => $detailsData,
                'form' => $form,
            ]
        ));
    }
}
