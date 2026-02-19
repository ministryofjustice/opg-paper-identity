<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\VoucherBirthDate;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Services\SiriusApiService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DateOfBirthHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly FormProcessorHelper $formProcessorHelper,
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
        $form = $this->createForm(VoucherBirthDate::class, $formData);

        $variables = [
            'details_data' => $detailsData,
            'form' => $form,
            'warning_message' => 'By continuing, you confirm that the person vouching is more than 100 years old.
            If not, please change the date.',
        ];

        if ($request->getMethod() === 'POST') {
            $dateOfBirth = $this->formProcessorHelper->processDateForm($formData);
            $form->setData([...$formData, 'date' => $dateOfBirth]);

            if ($form->isValid()) {
                $match = $this->checkMatchesForLpas(
                    $request,
                    $detailsData,
                    $dateOfBirth
                );

                if ($match !== false) {
                    $variables['match'] = $match;
                } else {
                    $proceed = $this->hasConfirmedOver100(
                        $dateOfBirth,
                        $formData
                    );

                    if ($proceed) {
                        $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);

                        $nextRoute = isset($detailsData["address"])
                            ? "voucher_enter_address_manual"
                            : "voucher_enter_postcode";

                        return $this->routeHelper->toRedirect($nextRoute, ['uuid' => $uuid]);
                    } else {
                        $variables['displaying_dob_100_warning'] = true;
                    }
                }
            }
        }

        if ($form->get('date')->getValue() === null && ! is_null($detailsData['dob'])) {
            $dob = DateTime::createFromFormat('Y-m-d', $detailsData['dob']);
            $form->setData([
                'dob_day' => date_format($dob, 'd'),
                'dob_month' => date_format($dob, 'm'),
                'dob_year' => date_format($dob, 'Y'),
            ]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/confirm_dob',
            $variables
        ));
    }

    /**
     * @param array<string, mixed> $postData
     */
    private function hasConfirmedOver100(
        string $dateOfBirth,
        array $postData,
    ): bool {
        $birthDate = strtotime($dateOfBirth);
        $maxBirthDate = strtotime('-100 years', time());

        if ($birthDate > $maxBirthDate) {
            return true;
        }

        if (isset($postData['dob_warning_100_accepted'])) {
            return true;
        }

        return false;
    }

    private function checkMatchesForLpas(
        ServerRequestInterface $request,
        array $detailsData,
        string $dateOfBirth
    ): bool | array {
        $match = false;
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $request);

            if (empty($lpasData)) {
                continue;
            }

            $match = $this->voucherMatchLpaActorHelper->checkMatch(
                $lpasData,
                $detailsData["firstName"],
                $detailsData["lastName"],
                $dateOfBirth,
            );
            // we raise the warning if there are any matches so stop once we've found one
            if ($match) {
                break;
            }
        }

        return $match;
    }
}
