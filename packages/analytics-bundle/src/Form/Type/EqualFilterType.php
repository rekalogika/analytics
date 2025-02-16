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

use Rekalogika\Analytics\Bundle\Form\Model\EqualFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @psalm-suppress MissingTemplateParam */
final class EqualFilterType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('values', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'choices' => $options['choices'],
                'choice_label' => $this->getChoiceLabel(...),
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['key'] = $options['key'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EqualFilter::class,
            'choices' => [],
            'key' => null,
        ]);
    }

    private function getChoiceLabel(mixed $value): string
    {
        if ($value instanceof \Stringable) {
            return (string) $value;
        } elseif ($value instanceof \BackedEnum) {
            return (string) $value->value;
        } elseif ($value instanceof \UnitEnum) {
            return $value->name;
        } elseif ($value instanceof TranslatableInterface) {
            return $value->trans($this->translator);
        } elseif (\is_scalar($value)) {
            return (string) $value;
        } else {
            return get_debug_type($value);
        }
    }
}
