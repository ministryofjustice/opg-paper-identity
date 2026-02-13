<?php

declare(strict_types=1);

namespace Application\Handler\Voucher\Address;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\AddressInput;
use Application\Helpers\RouteHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
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
        private readonly VoucherMatchLpaActorHelper $voucherMatchLpaActorHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = $request->getMethod() === 'POST' ? (array)($request->getParsedBody()) : $detailsData['address'];
        $form = $this->createForm(AddressInput::class, $formData);

        $countryList = $this->siriusApiService->getCountryList($request);

        $variables = [
            'details_data' => $detailsData,
            'form' => $form,
            'country_list' => $countryList,
        ];

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $addressMatch = false;
            foreach ($detailsData['lpas'] as $lpa) {
                $lpasData = $this->siriusApiService->getLpaByUid($lpa, $request);
                if (empty($lpasData)) {
                    continue;
                }
                $addressMatch = $addressMatch || $this->voucherMatchLpaActorHelper->checkAddressDonorMatch(
                    $lpasData,
                    $this->formToArray($form)
                );
            }
            if ($addressMatch) {
                $variables['address_match'] = true;
            } else {
                $this->opgApiService->addSelectedAddress($uuid, $this->formToArray($form));

                return $this->routeHelper->toRedirect('root/voucher_confirm_donors', ['uuid' => $uuid]);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/enter_address_manual',
            $variables,
        ));
    }
}
