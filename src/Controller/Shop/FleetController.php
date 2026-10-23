<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Controller\Shop;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\FleetScopeInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\CustomerAssetType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\CustomerAssetVoter;

/**
 * "My equipment" of the customer account. The fleet shown is the one of the signed-in customer
 * (and of their company). An asset is always loaded by its UUID and then authorised by the voter:
 * what the voter refuses is a 404, like what does not exist, so nothing tells that a foreign
 * machine exists. The plate or serial number is never logged and never put in a URL.
 */
final class FleetController extends AbstractController
{
    private const TEMPLATE = '@TmonierSyliusCustomerAssetManagementPlugin/shop/account/equipment/';

    public function __construct(
        private readonly FleetScopeInterface $fleetScope,
        private readonly CustomerAssetRepositoryInterface $assets,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly int $maxAssetsPerCustomer,
    ) {
    }

    public function indexAction(): Response
    {
        $customer = $this->customer();

        return $this->render(self::TEMPLATE . 'index.html.twig', [
            'assets' => $this->assets->findByOwners($this->fleetScope->ownerIds($customer)),
        ]);
    }

    public function createAction(Request $request): Response
    {
        $customer = $this->customer();
        $asset = new CustomerAsset();
        $asset->setCustomer($customer);

        return $this->handleForm($request, $asset, $customer, 'tmonier_equipment.asset_created');
    }

    public function updateAction(Request $request, string $id): Response
    {
        $asset = $this->accessibleAsset($id, CustomerAssetVoter::EDIT);

        return $this->handleForm($request, $asset, null, 'tmonier_equipment.asset_updated');
    }

    /** GET shows the confirmation page, POST deletes the machine. */
    public function deleteAction(Request $request, string $id): Response
    {
        $asset = $this->accessibleAsset($id, CustomerAssetVoter::DELETE);
        if (!$request->isMethod('POST')) {
            return $this->render(self::TEMPLATE . 'delete.html.twig', ['asset' => $asset]);
        }
        if (!$this->isCsrfTokenValid('tmonier_equipment_asset_delete_' . $asset->getId(), (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->entityManager->remove($asset);
        $this->entityManager->flush();
        $this->addFlash('success', 'tmonier_equipment.asset_deleted');

        return $this->redirectToRoute('tmonier_equipment_shop_fleet_index');
    }

    /** @param CustomerInterface|null $creator the customer who declares a new machine (limit of machines), null when editing */
    private function handleForm(Request $request, CustomerAssetInterface $asset, ?CustomerInterface $creator, string $flash): Response
    {
        $form = $this->createForm(CustomerAssetType::class, $asset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $creator instanceof CustomerInterface && $this->assets->countByOwner($creator) >= $this->maxAssetsPerCustomer) {
            $form->addError(new FormError($this->translator->trans('tmonier_equipment.asset.limit_reached', ['%count%' => $this->maxAssetsPerCustomer], 'validators')));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($asset);
            $this->entityManager->flush();
            $this->addFlash('success', $flash);

            return $this->redirectToRoute('tmonier_equipment_shop_fleet_index');
        }

        return $this->render(
            self::TEMPLATE . 'form.html.twig',
            ['form' => $form->createView(), 'asset' => $asset],
            new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK),
        );
    }

    private function accessibleAsset(string $id, string $attribute): CustomerAssetInterface
    {
        $asset = $this->assets->findOneById($id);
        if (!$asset instanceof CustomerAssetInterface || !$this->isGranted($attribute, $asset)) {
            throw $this->createNotFoundException('Not found.');
        }

        return $asset;
    }

    private function customer(): CustomerInterface
    {
        $user = $this->getUser();
        $customer = $user instanceof ShopUserInterface ? $user->getCustomer() : null;
        if (!$customer instanceof CustomerInterface) {
            throw $this->createAccessDeniedException();
        }

        return $customer;
    }
}
