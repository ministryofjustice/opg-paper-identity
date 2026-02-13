<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\AddDonor;
use Application\Helpers\AddDonorFormHelper;
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
        private readonly AddDonorFormHelper $addDonorFormHelper,
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
        $form = $this->createForm(AddDonor::class, $formData);

        $variables = [
            'form' => $form,
            'details_data' => $detailsData,
        ];

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            if (! empty($form->get('lpas')->getValue())) {
                if (! empty($form->get('declaration')->getValue())) {
                    foreach ($formData['lpas'] as $lpa) {
                        $this->opgApiService->updateCaseWithLpa($uuid, $lpa);
                    }

                    return $this->routeHelper->toRedirect('root/voucher_confirm_donors', ['uuid' => $uuid]);
                }

                $form->setMessages([
                    'declaration' => ['Confirm declaration to continue'],
                ]);
            }

            $lpas = $this->siriusApiService->getAllLinkedLpasByUid(
                $formData['lpa'],
                $request,
            );

            $processed = $this->addDonorFormHelper->processLpas($lpas, $detailsData);

            $variables['lpa_response'] = $processed;
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/vouch_for_another_donor',
            $variables,
        ));
    }
}
