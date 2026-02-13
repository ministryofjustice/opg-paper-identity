<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\VoucherName;
use Application\Helpers\RouteHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NameHandler implements RequestHandlerInterface
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

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(VoucherName::class, $formData);

        $variables = [
            'details_data' => $detailsData,
            'form' => $form,
        ];

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $match = false;
            foreach ($detailsData['lpas'] as $lpa) {
                $lpasData = $this->siriusApiService->getLpaByUid($lpa, $request);
                if (empty($lpasData)) {
                    continue;
                }

                $match = $this->voucherMatchLpaActorHelper->checkMatch(
                    $lpasData,
                    $formData["firstName"],
                    $formData["lastName"],
                );

                // we raise the warning if there are any matches so stop once we've found one
                if ($match) {
                    break;
                }
            }

            if ($match && $form->get('continue-after-warning')->getValue() === null) {
                $variables['match'] = $match;
                $variables['matched_name'] = $formData["firstName"] . ' ' . $formData["lastName"];
            } else {
                $this->opgApiService->updateCaseSetName($uuid, $formData["firstName"], $formData["lastName"]);

                return $this->routeHelper->toRedirect("root/voucher_dob", ['uuid' => $uuid]);
            }
        } else {
            $form->setData([
                "firstName" => $detailsData["firstName"],
                "lastName" => $detailsData["lastName"],
            ]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/what_is_the_voucher_name',
            $variables,
        ));
    }
}
