<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

/** @psalm-suppress MissingTemplateParam */
class SummaryQueryType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rows', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('columns', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('values', ChoiceType::class, [
                'multiple' => true,
                'required' => true,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('filters', ChoiceType::class, [
                'multiple' => true,
                'required' => true,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                $this->onPreSetData(...),
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PivotAwareSummaryQuery::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    private function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data instanceof PivotAwareSummaryQuery) {
            throw new \InvalidArgumentException('Data must be an instance of PivotAwareSummaryQuery');
        }

        $dimensionChoices = $data->getDimensionChoices();
        $measuresChoices = $data->getMeasureChoices();

        $form
            ->add('rows', ChoiceType::class, [
                'choices' => array_keys($dimensionChoices),
                'choice_label' => fn(string $choice): TranslatableInterface => $dimensionChoices[$choice],
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('columns', ChoiceType::class, [
                'choices' => array_keys($dimensionChoices),
                'choice_label' => fn(string $choice): TranslatableInterface => $dimensionChoices[$choice],
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('values', ChoiceType::class, [
                'choices' => array_keys($measuresChoices),
                'choice_label' => fn(string $choice): TranslatableInterface => $measuresChoices[$choice],
                'multiple' => true,
                'required' => true,
                'attr' => [
                    'size' => 12,
                ],
            ])
            ->add('filters', ChoiceType::class, [
                'choices' => array_keys($dimensionChoices),
                'choice_label' => fn(string $choice): TranslatableInterface => $dimensionChoices[$choice],
                'multiple' => true,
                'required' => true,
                'attr' => [
                    'size' => 12,
                ],
            ]);
    }
}
