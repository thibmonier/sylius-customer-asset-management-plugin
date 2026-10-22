<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;

/** The machine of a customer: model, free label, plate or serial number. The owner is never a field. */
final class CustomerAssetType extends AbstractType
{
    /** @param class-string<EquipmentModelInterface> $modelClass */
    public function __construct(private readonly string $modelClass)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('equipmentModel', EntityType::class, [
                'class' => $this->modelClass,
                'query_builder' => static fn (EntityRepository $repository): \Doctrine\ORM\QueryBuilder => $repository instanceof EquipmentModelRepositoryInterface
                    ? $repository->createChoiceQueryBuilder()
                    : $repository->createQueryBuilder('o')->orderBy('o.code'),
                'choice_label' => static fn (EquipmentModelInterface $model): string => $model->getName() ?? (string) $model->getCode(),
                'placeholder' => 'tmonier_equipment.form.asset.choose_model',
                'label' => 'tmonier_equipment.form.asset.equipment_model',
                'invalid_message' => 'tmonier_equipment.asset.equipment_model.invalid',
            ])
            ->add('label', TextType::class, [
                'label' => 'tmonier_equipment.form.asset.label',
                'help' => 'tmonier_equipment.form.asset.label_help',
                'attr' => ['maxlength' => CustomerAssetInterface::LABEL_MAX_LENGTH],
            ])
            ->add('identifier', TextType::class, [
                'label' => 'tmonier_equipment.form.asset.identifier',
                'help' => 'tmonier_equipment.form.asset.identifier_help',
                'attr' => ['maxlength' => CustomerAssetInterface::IDENTIFIER_MAX_LENGTH, 'autocomplete' => 'off'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CustomerAsset::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'tmonier_equipment_customer_asset';
    }
}
