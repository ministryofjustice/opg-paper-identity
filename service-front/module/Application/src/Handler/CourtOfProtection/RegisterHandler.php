<?php

declare(strict_types=1);

namespace Application\Handler\CourtOfProtection;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ConfirmCourtOfProtection;
use Application\Helpers\RouteHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegisterHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(ConfirmCourtOfProtection::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $this->opgApiService->sendIdentityCheck($uuid);
            $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $request);

            return $this->routeHelper->toRedirect(
                'root/court_of_protection_what_next',
                ['uuid' => $uuid]
            );
        }

        $lpaDetails = $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $request);
        $hasFraudMarker = isset($detailsData["caseProgress"]["fraudScore"]["decision"])
            && $detailsData["caseProgress"]["fraudScore"]["decision"] === "STOP";

        return new HtmlResponse($this->renderer->render(
            'application/pages/court_of_protection',
            [
                'details_data' => $detailsData,
                'form' => $form,
                'has_fraud_marker' => $hasFraudMarker,
                'lpa_details' => $lpaDetails,
            ]
        ));
    }
}
