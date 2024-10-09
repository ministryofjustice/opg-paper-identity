<?php

declare(strict_types=1);

namespace Application\Controller\Trait;

use Application\Forms\FormTemplate;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;

trait FormBuilder
{
    /**
     * @template U
     * @template T of FormTemplate<U>
     * @param class-string<T> $form
     * @return FormInterface<U>
     */
    public function createForm($form)
    {
        $form = (new AttributeBuilder())->createForm($form);
        $form->setData($this->getRequest()->getPost());

        return $form;
    }
}
