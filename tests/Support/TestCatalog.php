<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyMemberInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Factory\CompanyMemberFactoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Webmozart\Assert\Assert;

/**
 * Data created by the tests themselves, inside their rolled-back transaction, so that no test
 * depends on the random Sylius fixtures.
 */
final class TestCatalog
{
    public readonly string $suffix;

    private int $sequence = 0;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->suffix = strtolower(bin2hex(random_bytes(4)));
    }

    public function channel(): ChannelInterface
    {
        $repository = $this->entityManager()->getRepository(ChannelInterface::class);
        $channel = $repository->findOneBy(['hostname' => 'localhost']) ?? $repository->findOneBy(['enabled' => true]);
        Assert::isInstanceOf($channel, ChannelInterface::class);

        return $channel;
    }

    public function localeCode(): string
    {
        return (string) $this->channel()->getDefaultLocale()?->getCode();
    }

    /** A customer with an enabled, verified shop account (random password: tests sign in with loginUser()). */
    public function customer(string $name = 'buyer'): CustomerInterface
    {
        /** @var FactoryInterface<CustomerInterface> $customerFactory */
        $customerFactory = $this->container->get('sylius.factory.customer');
        $customer = $customerFactory->createNew();
        $customer->setEmail(sprintf('%s-%d-%s@customer-assets.test', $name, ++$this->sequence, $this->suffix));
        $customer->setFirstName('Ada');
        $customer->setLastName('Lovelace');
        /** @var FactoryInterface<ShopUserInterface> $userFactory */
        $userFactory = $this->container->get('sylius.factory.shop_user');
        $user = $userFactory->createNew();
        $user->setPlainPassword(bin2hex(random_bytes(16)));
        $user->setEnabled(true);
        $user->setVerifiedAt(new \DateTime());
        $customer->setUser($user);
        $this->entityManager()->persist($customer);
        $this->entityManager()->flush();

        return $customer;
    }

    public function company(string $name = 'Acme'): CompanyInterface
    {
        /** @var FactoryInterface<CompanyInterface> $factory */
        $factory = $this->container->get('tmonier_company_accounts.factory.company');
        $company = $factory->createNew();
        $company->setCode(strtoupper(sprintf('%s_%s_%d', $name, $this->suffix, ++$this->sequence)));
        $company->setName($name . ' ' . $this->suffix);
        $company->setEnabled(true);
        $this->entityManager()->persist($company);
        $this->entityManager()->flush();

        return $company;
    }

    public function member(CompanyInterface $company, ?CustomerInterface $customer = null): CompanyMemberInterface
    {
        /** @var CompanyMemberFactoryInterface $factory */
        $factory = $this->container->get('tmonier_company_accounts.factory.company_member');
        $member = $factory->createForCustomer($company, $customer ?? $this->customer('member'), CompanyMemberInterface::ROLE_BUYER);
        $member->setEnabled(true);
        $this->entityManager()->persist($member);
        $this->entityManager()->flush();

        return $member;
    }

    /** @param array<string, string> $names locale code => name (a model without name in a locale is shown by its code) */
    public function model(string $code = 'TRUCK', array $names = []): EquipmentModelInterface
    {
        /** @var FactoryInterface<EquipmentModelInterface> $factory */
        $factory = $this->container->get('tmonier_equipment.factory.equipment_model');
        $model = $factory->createNew();
        $model->setCode(sprintf('%s-%s-%d', $code, $this->suffix, ++$this->sequence));
        foreach ($names as $locale => $name) {
            $model->setCurrentLocale($locale);
            $model->setName($name);
        }
        $this->entityManager()->persist($model);
        $this->entityManager()->flush();

        return $model;
    }

    public function taxon(string $code = 'PARTS'): TaxonInterface
    {
        /** @var FactoryInterface<TaxonInterface> $factory */
        $factory = $this->container->get('sylius.factory.taxon');
        $taxon = $factory->createNew();
        $taxon->setCode(sprintf('%s_%s_%d', $code, $this->suffix, ++$this->sequence));
        $taxon->setCurrentLocale($this->localeCode());
        $taxon->setFallbackLocale($this->localeCode());
        $taxon->setName($code);
        $taxon->setSlug(strtolower(sprintf('%s-%s-%d', $code, $this->suffix, $this->sequence)));
        $this->entityManager()->persist($taxon);
        $this->entityManager()->flush();

        return $taxon;
    }

    /** An enabled product with one variant, listed in the taxon when given. */
    public function product(string $name, ?TaxonInterface $taxon = null): ProductVariantInterface
    {
        $channel = $this->channel();
        $locale = $this->localeCode();
        /** @var ProductFactoryInterface<ProductInterface> $productFactory */
        $productFactory = $this->container->get('sylius.factory.product');
        /** @var ProductInterface $product */
        $product = $productFactory->createWithVariant();
        $code = sprintf('P%d_%s', ++$this->sequence, $this->suffix);
        $product->setCode($code);
        $product->setCurrentLocale($locale);
        $product->setFallbackLocale($locale);
        $product->setName($name);
        $product->setSlug(strtolower(str_replace('_', '-', $code)));
        $product->setEnabled(true);
        $product->addChannel($channel);
        $product->setMainTaxon($taxon);
        if ($taxon instanceof TaxonInterface) {
            /** @var FactoryInterface<ProductTaxonInterface> $productTaxonFactory */
            $productTaxonFactory = $this->container->get('sylius.factory.product_taxon');
            $productTaxon = $productTaxonFactory->createNew();
            $productTaxon->setTaxon($taxon);
            $productTaxon->setProduct($product);
            $product->addProductTaxon($productTaxon);
        }

        $variant = $product->getVariants()->first();
        Assert::isInstanceOf($variant, ProductVariantInterface::class);
        $variant->setCode($code . '-V');
        $variant->setCurrentLocale($locale);
        $variant->setName($name . ' variant');
        $variant->setEnabled(true);
        $variant->setTracked(false);
        /** @var FactoryInterface<ChannelPricingInterface> $pricingFactory */
        $pricingFactory = $this->container->get('sylius.factory.channel_pricing');
        $pricing = $pricingFactory->createNew();
        $pricing->setChannelCode((string) $channel->getCode());
        $pricing->setPrice(2500);
        $pricing->setOriginalPrice(2500);
        $variant->addChannelPricing($pricing);

        $this->entityManager()->persist($product);
        $this->entityManager()->flush();

        return $variant;
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        return $entityManager;
    }
}
