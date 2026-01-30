<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\PersonType;
use Application\Forms\CountryDocument;
use Application\Helpers\RouteHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentTypeRepository;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChooseInternationalDocumentHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (! isset($detailsData['idMethod']['idCountry'])) {
            throw new \Exception("Country for document list has not been set.");
        }

        $country = PostOfficeCountry::from($detailsData['idMethod']['idCountry']);

        $docs = $this->documentTypeRepository->getByCountry($country);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(CountryDocument::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $formData = $this->formToArray($form);
            $this->opgApiService->updateIdMethod($uuid, $formData);

            switch ($detailsData["personType"]) {
                case PersonType::Voucher:
                    $redirect = "root/voucher_name";

                    break;
                case PersonType::CertificateProvider:
                    $redirect = "root/cp_name_match_check";

                    break;
                default:
                    $redirect = "root/donor_details_match_check";

                    break;
            }

            return $this->routeHelper->toRedirect($redirect, ['uuid' => $uuid]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/post_office/choose_country_id',
            [
                'form' => $form,
                'countryName' => $country->translate(),
                'details_data' => $detailsData,
                'supported_docs' => $docs,
            ]
        ));
    }
}
