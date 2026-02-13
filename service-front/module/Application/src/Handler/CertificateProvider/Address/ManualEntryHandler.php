<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider\Address;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\AddressInput;
use Application\Helpers\RouteHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ManualEntryHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = $request->getMethod() === 'POST' ? (array)($request->getParsedBody()) : $detailsData['address'];
        $form = $this->createForm(AddressInput::class, $formData);

        $countryList = $this->siriusApiService->getCountryList($request);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $this->opgApiService->updateCaseAddress($uuid, $this->formToArray($form));

            $existingAddress = $detailsData['address'];

            if (! isset($detailsData['professionalAddress'])) {
                $this->opgApiService->updateCaseProfessionalAddress($uuid, $existingAddress);
            }

            return $this->routeHelper->toRedirect('root/cp_confirm_address', ['uuid' => $uuid]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/cp/enter_address_manual',
            [
                'details_data' => $detailsData,
                'form' => $form,
                'country_list' => $countryList,
            ]
        ));
    }
}
