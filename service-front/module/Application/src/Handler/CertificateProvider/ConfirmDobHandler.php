<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\BirthDate;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmDobHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(BirthDate::class, $formData);


        $variables = [
            'form' => $form,
            'include_fraud_id_check_info' => true,
            'warning_message' => 'By continuing, you confirm that the certificate provider is more than 100 years old.
            If not, please change the date.',
        ];

        if ($request->getMethod() === 'POST') {
            $dateOfBirth = $this->formProcessorHelper->processDateForm($formData);
            $form->setData([...$formData, 'date' => $dateOfBirth]);

            if ($form->isValid()) {
                $proceed = $this->hasConfirmedOver100(
                    $dateOfBirth,
                    $formData
                );

                if ($proceed) {
                    $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);

                    return $this->routeHelper->toRedirect('root/cp_confirm_address', ['uuid' => $uuid]);
                } else {
                    $variables['displaying_dob_100_warning'] = true;
                }
            }
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $variables['details_data'] = $detailsData;

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
            $variables,
        ));
    }

    /**
     * @param array<string, mixed> $postData
     */
    protected function hasConfirmedOver100(
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
}
