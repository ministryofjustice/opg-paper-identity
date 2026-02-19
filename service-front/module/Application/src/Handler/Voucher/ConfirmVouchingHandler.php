<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ConfirmVouching;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Router\RouteStackInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmVouchingHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly RouteStackInterface $routeStack,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(ConfirmVouching::class, $formData);

        if ($request->getMethod() === 'POST') {
            $validity = $form->isValid();

            if ($form->get('tryDifferent')->getValue()) {
                $startUrl = $this->routeStack->assemble([], ['name' => 'start'])
                    . "?personType=donor&lpas[]=" . implode("&lpas[]=", $detailsData['lpas']);

                return new RedirectResponse($startUrl);
            }

            if ($validity) {
                return $this->routeHelper->toRedirect("root/how_will_you_confirm", ['uuid' => $uuid]);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/confirm_vouching',
            [
                'details_data' => $detailsData,
                'form' => $form,
            ]
        ));
    }
}
