<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\LpaReferenceNumber;
use Application\Forms\LpaReferenceNumberAdd;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\RouteHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddLpaHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly LpaFormHelper $lpaFormHelper,
        private readonly RouteHelper $routeHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());

        $searchForm = $this->createForm(LpaReferenceNumber::class, $formData);
        $selectForm = $this->createForm(LpaReferenceNumberAdd::class, $formData);

        $variables = [
            'details_data' => $detailsData,
            'form' => $searchForm,
        ];

        if ($request->getMethod() === 'POST') {
            if ($selectForm->isValid()) {
                $this->opgApiService->updateCaseWithLpa(
                    $uuid,
                    $selectForm->get('add_lpa_number')->getValue()
                );

                return $this->routeHelper->toRedirect('root/cp_confirm_lpas', ['uuid' => $uuid]);
            }

            if ($searchForm->isValid()) {
                $siriusCheck = $this->siriusApiService->getLpaByUid(
                    $searchForm->get('lpa')->getValue(),
                    $request,
                );

                $processed = $this->lpaFormHelper->findLpa(
                    $uuid,
                    $searchForm,
                    $siriusCheck,
                    $detailsData,
                );

                $variables['lpa_response'] = $processed->constructFormVariables();
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                'application/pages/cp/add_lpa',
                $variables,
            )
        );
    }
}
