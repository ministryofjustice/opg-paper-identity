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
    protected function createForm($form, ?iterable $formData = null)
    {
        $form = (new AttributeBuilder())->createForm($form);
        $form->setData($formData ?? $this->getRequest()->getPost());

        return $form;
    }

    /**
     * @template T
     * @param FormInterface<T> $form
     * @return T
     */
    protected function formToArray($form)
    {
        return $form->getData(FormInterface::VALUES_AS_ARRAY);
    }
}
