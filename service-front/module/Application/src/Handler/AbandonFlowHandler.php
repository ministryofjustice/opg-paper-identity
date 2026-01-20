<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\AbandonFlow;
use Application\Helpers\RouteHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\View\Model\ViewModel;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AbandonFlowHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = new ViewModel();
        $uuid = $request->getAttribute('uuid');

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lastPage = $request->getQueryParams()['last_page'] ?? null;

        $postData = (array)($request->getParsedBody());
        $form = $this->createForm(AbandonFlow::class, $postData);

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $caseProgressData = $detailsData['caseProgress'] ?? [];

            $caseProgressData['abandonedFlow'] = [
                'last_page' => $lastPage,
                'timestamp' => date("Y-m-d\TH:i:s\Z", time()),
            ];

            $this->opgApiService->updateCaseProgress($uuid, $caseProgressData);
            $this->opgApiService->sendIdentityCheck($uuid);

            $this->sendNoteHelper->sendAbandonFlowNote(
                $postData['reason'],
                $postData['notes'],
                $detailsData['lpas'],
                $request
            );

            $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $request);
            $siriusUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

            return new RedirectResponse($siriusUrl);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $lastPage);
        $view->setVariable('form', $form);

        return new HtmlResponse($this->renderer->render('application/pages/abandoned_flow', $view->getVariables()));
    }
}
