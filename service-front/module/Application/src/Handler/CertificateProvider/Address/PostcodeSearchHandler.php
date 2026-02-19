<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider\Address;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Exceptions\PostcodeInvalidException;
use Application\Forms\Postcode;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PostcodeSearchHandler implements RequestHandlerInterface
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
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(Postcode::class, $formData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $postcode = $form->get('postcode')->getValue();

            try {
                $response = $this->siriusApiService->searchAddressesByPostcode($postcode, $request);

                if (! empty($response)) {
                    return $this->routeHelper->toRedirect(
                        'cp_select_address',
                        [
                            'uuid' => $uuid,
                            'postcode' => $postcode,
                        ]
                    );
                }
            } catch (PostcodeInvalidException $e) {
                // Continue to error message
            }

            $form->setMessages([
                'postcode' => [$this->addressProcessorHelper::ERROR_POSTCODE_NOT_FOUND],
            ]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/cp/enter_address',
            [
                'details_data' => $detailsData,
                'form' => $form,
            ]
        ));
    }
}
