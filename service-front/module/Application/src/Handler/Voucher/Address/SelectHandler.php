<?php

declare(strict_types=1);

namespace Application\Handler\Voucher\Address;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\AddressJson;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SelectHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly AddressProcessorHelper $addressProcessorHelper,
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $postcode = $request->getAttribute('postcode');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(AddressJson::class, $formData);

        $response = $this->siriusApiService->searchAddressesByPostcode($postcode, $request);

        $processedAddresses = [];
        foreach ($response as $foundAddress) {
            $processedAddresses[] = $this->addressProcessorHelper->processAddress(
                $foundAddress,
                'siriusAddressType'
            );
        }

        $addressStrings = $this->addressProcessorHelper->stringifyAddresses($processedAddresses);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $structuredAddress = json_decode($formData['address_json'], true);

            $this->opgApiService->addSelectedAddress($uuid, $structuredAddress);

            return $this->routeHelper->toRedirect('voucher_enter_address_manual', ['uuid' => $uuid]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/select_address',
            [
                'details_data' => $detailsData,
                'form' => $form,
                'addresses' => $addressStrings,
                'addresses_count' => count($addressStrings),
            ]
        ));
    }
}
