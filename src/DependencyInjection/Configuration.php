<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Resource\Factory\Factory;
use Sylius\Resource\Factory\TranslatableFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModel;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelTranslation;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelTranslationInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelTranslationType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepository;

final class Configuration implements ConfigurationInterface
{
    public const DEFAULT_ADMIN_ROLE = 'ROLE_ADMINISTRATION_ACCESS';

    public const DEFAULT_MAX_ASSETS_PER_CUSTOMER = 50;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tmonier_sylius_customer_asset_management');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('admin_role')
                    ->info('Role an administrator needs to manage the equipment models. Defaults to the access to the administration; set a dedicated role to restrict the screens.')
                    ->defaultValue(self::DEFAULT_ADMIN_ROLE)
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('max_assets_per_customer')
                    ->info('Number of machines a customer can declare. Bounds the query of the "compatible parts" filter.')
                    ->defaultValue(self::DEFAULT_MAX_ASSETS_PER_CUSTOMER)
                    ->min(1)
                ->end()
                ->scalarNode('driver')->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)->end()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('equipment_model')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(EquipmentModel::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(EquipmentModelInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(EquipmentModelRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->end()
                                        ->scalarNode('form')->defaultValue(EquipmentModelType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                                ->arrayNode('translation')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('classes')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->defaultValue(EquipmentModelTranslation::class)->cannotBeEmpty()->end()
                                                ->scalarNode('interface')->defaultValue(EquipmentModelTranslationInterface::class)->cannotBeEmpty()->end()
                                                ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                                ->scalarNode('repository')->cannotBeEmpty()->end()
                                                ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                                ->scalarNode('form')->defaultValue(EquipmentModelTranslationType::class)->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
