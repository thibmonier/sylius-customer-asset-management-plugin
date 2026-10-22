<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class TmonierSyliusCustomerAssetManagementExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public const RESOURCES_APPLICATION = 'tmonier_equipment';

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{admin_role: string, max_assets_per_customer: int, driver: string, resources: array<string, mixed>} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $this->registerResources(self::RESOURCES_APPLICATION, $config['driver'], $config['resources'], $container);
        $container->setParameter('tmonier_equipment.admin_role', $config['admin_role']);
        $container->setParameter('tmonier_equipment.max_assets_per_customer', $config['max_assets_per_customer']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Grids are processed before this extension is loaded: the model class parameters have to
        // exist at prepend time.
        $configs = array_map(
            static fn (array $config): array => isset($config['resources']) ? ['resources' => $config['resources']] : [],
            $container->getExtensionConfig($this->getAlias()),
        );
        /** @var array{resources: array<string, array{classes: array{model: string}}>} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);
        foreach ($config['resources'] as $name => $resource) {
            $container->setParameter(sprintf('%s.model.%s.class', self::RESOURCES_APPLICATION, $name), $resource['classes']['model']);
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'TmonierSyliusCustomerAssetManagementPlugin' => [
                        'type' => 'xml',
                        'dir' => 'config/doctrine',
                        'prefix' => 'Tmonier\SyliusCustomerAssetManagementPlugin\Entity',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('framework', [
            'validation' => ['mapping' => ['paths' => [\dirname(__DIR__, 2) . '/config/validation']]],
        ]);

        $this->prependDoctrineMigrations($container);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_customer_asset_management';
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Tmonier\SyliusCustomerAssetManagementPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@TmonierSyliusCustomerAssetManagementPlugin/src/Migrations';
    }

    /** @return list<string> */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
