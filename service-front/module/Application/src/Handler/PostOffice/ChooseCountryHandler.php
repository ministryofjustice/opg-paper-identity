<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\Country;
use Application\Helpers\RouteHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChooseCountryHandler implements RequestHandlerInterface
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
        $uuid = $request->getAttribute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(Country::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $formData = $this->formToArray($form);

            $this->opgApiService->updateIdMethod($uuid, $formData);

            return $this->routeHelper->toRedirect("root/po_choose_country_id", ['uuid' => $uuid]);
        }

        $countriesData = PostOfficeCountry::cases();
        $countriesData = array_filter(
            $countriesData,
            fn (PostOfficeCountry $country) => $country !== PostOfficeCountry::GBR
        );

        return new HtmlResponse($this->renderer->render(
            'application/pages/post_office/choose_country',
            [
                'form' => $form,
                'countries_data' => $countriesData,
                'details_data' => $detailsData,
                'uuid' => $uuid,
            ]
        ));
    }
}
