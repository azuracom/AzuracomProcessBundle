<?php

namespace Azuracom\ProcessBundle\Form;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatusChoiceType extends AbstractType
{

    public function __construct(private ProcessHelperInterface $processHelper)
    {
    }

    public function getParent() : ?string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_flip($this->processHelper->getStatusList()),
        ]);
    }
}
