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

namespace Rekalogika\Analytics\Bundle\Form\Type;

use Rekalogika\Analytics\Bundle\Form\Model\FilterExpressions;
use Rekalogika\Analytics\DistinctValuesResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @psalm-suppress MissingTemplateParam */
class FilterExpressionsType extends AbstractType
{
    public function __construct(
        private DistinctValuesResolver $distinctValuesResolver,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                $this->onPreSetData(...),
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilterExpressions::class,
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
        $filterExpressions = $event->getData();
        $form = $event->getForm();

        if (!$filterExpressions instanceof FilterExpressions) {
            throw new \LogicException('Data must be instance of FilterExpressions');
        }

        $summaryClass = $filterExpressions->getSummaryClass();

        foreach ($filterExpressions as $key => $filter) {
            if ($key === '@values') {
                continue;
            }

            $choices = $this->distinctValuesResolver
                ->getDistinctValues($summaryClass, $key, 100);

            if ($choices === null) {
                continue;
            }

            /** @psalm-suppress InvalidArgument */
            $choices = iterator_to_array($choices);
            // dump($choices);

            $skey = str_replace('.', '__', $key);

            $form->add($skey, EqualFilterType::class, [
                'property_path' => '[' . $key . ']',
                'choices' => $choices,
                'key' => $key,
            ]);
        }
    }
}
