<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Controller\Trait\FormBuilder;
use Application\Enums\LpaStatusType;
use Application\Enums\PersonType;
use Application\Exceptions\HttpException;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\LpaStatusTypeHelper;
use Application\Helpers\RouteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StartHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly LpaFormHelper $lpaFormHelper,
        private readonly RouteHelper $routeHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string[] $lpasQuery */
        $lpasQuery = $request->getQueryParams()["lpas"];
        $type = $request->getQueryParams()["personType"];

        try {
            $personType = PersonType::from($type);
        } catch (\ValueError) {
            throw new HttpException(
                400,
                "Person type '$type' is not valid"
            );
        }

        $pageVariables = [];

        $lpas = [];
        $unfoundLpas = [];
        foreach ($lpasQuery as $key => $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $request);

            if (empty($data)) {
                $unfoundLpas[] = $lpaUid;
                $pageVariables = [
                    'sirius_url' => $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $lpasQuery[0],
                    'details_data' => [
                        'personType' => $personType,
                        'firstName' => '',
                        'lastName' => '',
                    ],
                ];
                unset($lpasQuery[$key]);
            } else {
                $lpas[] = $data;
            }
        }

        $lpasQuery = array_values($lpasQuery);

        if (empty($lpas)) {
            $lpsString = implode(", ", $unfoundLpas);
            $pageVariables['message'] = 'LPA not found for ' . $lpsString;

            return new HtmlResponse($this->renderer->render(
                'application/pages/cannot_start',
                $pageVariables
            ));
        }

        if (! $this->lpaFormHelper->lpaIdentitiesMatch($lpas, $personType)) {
            $personTypeDescription = [
                'donor' => "Donors",
                'voucher' => "Donors",
                'certificateProvider' => "Certificate Providers",
            ];

            throw new HttpException(
                400,
                "These LPAs are for different {$personTypeDescription[$personType->value]}"
            );
        }

        try {
            $lpaStatusCheck = new LpaStatusTypeHelper($lpas[0], $personType);

            if (! $lpaStatusCheck->isStartable()) {
                $lpaStatusTypeCheck = $lpaStatusCheck->getStatus() === LpaStatusType::Registered ?
                    "The identity check has already been completed" :
                    "ID check has status: " . $lpaStatusCheck->getStatus()->value . " and cannot be started";

                return new HtmlResponse($this->renderer->render(
                    'application/pages/cannot_start',
                    [
                        'message' => $lpaStatusTypeCheck,
                        'sirius_url' => $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $lpasQuery[0],
                        'details_data' => $this->constructDetailsDataBeforeCreatedCase($lpas[0], $personType),
                    ]
                ));
            }
        } catch (\Exception $exception) {
            throw new HttpException(400, $exception->getMessage());
        }

        $case = $this->siriusDataProcessorHelper->createPaperIdCase($personType, $lpasQuery, $lpas[0]);

        if ($personType === PersonType::Voucher) {
            $redirect = 'confirm_vouching';
        } else {
            $redirect = 'how_will_you_confirm';
        }

        return $this->routeHelper->toRedirect($redirect, ['uuid' => $case['uuid']]);
    }

    private function constructDetailsDataBeforeCreatedCase(array $lpa, PersonType $personType): array
    {
        $processed = $this->siriusDataProcessorHelper->processLpaResponse(
            $personType,
            $lpa
        );

        return [
            'personType' => $personType,
            'firstName' => $processed['first_name'],
            'lastName' => $processed['last_name'],
        ];
    }
}
