<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;

/**
 * "Compatible models" on the variant form. Compatibility is owned by the equipment model, so the
 * choice is not mapped on the variant: it is synchronised on submit.
 */
final class ProductVariantTypeExtension extends AbstractTypeExtension
{
    private const FIELD = 'equipmentModels';

    private const MARKER = 'equipmentModelsPresent';

    /** @param class-string<EquipmentModelInterface> $modelClass */
    public function __construct(
        private readonly EquipmentModelRepositoryInterface $repository,
        private readonly EquipmentAdminAccess $access,
        private readonly string $modelClass,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductVariantType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->access->canManageModels()) {
            return;
        }

        $builder
            ->add(self::FIELD, EntityType::class, [
                'class' => $this->modelClass,
                'query_builder' => static fn (EntityRepository $repository): \Doctrine\ORM\QueryBuilder => $repository instanceof EquipmentModelRepositoryInterface
                    ? $repository->createChoiceQueryBuilder()
                    : $repository->createQueryBuilder('o')->orderBy('o.code'),
                'choice_label' => static fn (EquipmentModelInterface $model): string => null === $model->getName()
                    ? (string) $model->getCode()
                    : sprintf('%s (%s)', $model->getName(), $model->getCode()),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'mapped' => false,
                'label' => 'tmonier_equipment.form.variant.compatible_models',
                'help' => 'tmonier_equipment.form.variant.compatible_models_help',
            ])
            // A group of checkboxes with none ticked is absent from the request: the marker tells a
            // form that shows the field (everything unticked) from one that does not (the variant
            // embedded in the form of a simple product).
            ->add(self::MARKER, HiddenType::class, ['mapped' => false, 'data' => '1'])
            ->addEventListener(FormEvents::POST_SET_DATA, $this->fill(...))
            ->addEventListener(FormEvents::PRE_SUBMIT, $this->ignoreWhenNotRendered(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->synchronise(...))
        ;
    }

    private function fill(FormEvent $event): void
    {
        $variant = $event->getData();
        if ($variant instanceof ProductVariantInterface && $event->getForm()->has(self::FIELD)) {
            $event->getForm()->get(self::FIELD)->setData($this->repository->findByVariant($variant));
        }
    }

    private function ignoreWhenNotRendered(FormEvent $event): void
    {
        $submitted = $event->getData();
        if (\is_array($submitted) && isset($submitted[self::MARKER])) {
            return;
        }

        $form = $event->getForm();
        $form->remove(self::FIELD);
        $form->remove(self::MARKER);
    }

    private function synchronise(FormEvent $event): void
    {
        $form = $event->getForm();
        $variant = $event->getData();
        if (!$variant instanceof ProductVariantInterface || !$form->has(self::FIELD) || !$form->isValid()) {
            return;
        }

        /** @var iterable<EquipmentModelInterface> $selected */
        $selected = $form->get(self::FIELD)->getData() ?? [];
        $selectedIds = [];
        foreach ($selected as $model) {
            $selectedIds[$model->getId() ?? 0] = true;
            $model->addCompatibleVariant($variant);
        }

        foreach ($this->repository->findByVariant($variant) as $model) {
            if (!isset($selectedIds[$model->getId() ?? 0])) {
                $model->removeCompatibleVariant($variant);
            }
        }
    }
}
