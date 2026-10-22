<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\EventSubscriber\AddCodeFormSubscriber;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\FormBuilderInterface;

final class EquipmentModelType extends AbstractResourceType
{
    public function __construct(string $dataClass, array $validationGroups, private readonly string $translationType)
    {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // The code is read only once the model exists: a compatibility already entered must not break.
            ->addEventSubscriber(new AddCodeFormSubscriber())
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => $this->translationType,
                'label' => 'tmonier_equipment.form.equipment_model.translations',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'tmonier_equipment_equipment_model';
    }
}
