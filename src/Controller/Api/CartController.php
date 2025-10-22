<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Exception\CartItemNotFoundException;
use App\Exception\CartNotFoundException;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
    ) {
    }

    #[Route('/api/carts', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $carts = $this->cartRepository->findAll();

        $groups = ['cart:list'];
        if ('items' === $request->query->get('expand')) {
            $groups[] = 'cart:read';
        }

        return $this->json($carts, 200, [], ['groups' => $groups]);
    }

    #[Route('/api/carts', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $cart = new Cart();

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $this->json($cart, 201, [], ['groups' => ['cart:read']]);
    }

    /**
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $cart = $this->findCartOrFail($id);

        return $this->json($cart, 200, [], ['groups' => ['cart:read']]);
    }

    /**
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}/items', methods: ['POST'])]
    public function addItem(string $id, Request $request): JsonResponse
    {
        $cart = $this->findCartOrFail($id);

        $data = $request->toArray();
        $errors = [];
        foreach (CartItem::requiredFields() as $requiredField) {
            if (!isset($data[$requiredField])) {
                $code = strtoupper($requiredField).'_REQUIRED';
                $errors[$code] = ucfirst($requiredField).' required';
            }
        }

        if ($errors) {
            return $this->json([
                'error' => [
                    'message' => implode(' | ', $errors),
                    'code' => implode('|', array_keys($errors)),
                ],
            ], 400);
        }

        $validationErrors = $this->validateItemFields($data);
        if ($validationErrors) {
            return $this->json([
                'error' => [
                    'message' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validationErrors,
                ],
            ], 400);
        }

        $item = new CartItem(
            $cart,
            $data['productId'],
            $data['productName'],
            $data['price'],
            $data['quantity'],
            $data['category'] ?? null,
            $data['sku'] ?? null
        );

        $cart->addItem($item);

        $this->entityManager->flush();

        return $this->json($cart, 201, [], ['groups' => ['cart:read']]);
    }

    /**
     * @throws CartItemNotFoundException
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}/items/{itemId}', methods: ['PATCH'])]
    public function updateItem(string $id, string $itemId, Request $request): JsonResponse
    {
        $cart = $this->findCartOrFail($id);

        $data = $request->toArray();
        if (!isset($data['quantity'])) {
            return $this->json([
                'error' => [
                    'message' => 'Quantity required',
                    'code' => 'QUANTITY_REQUIRED',
                ],
            ], 400);
        }

        $item = $cart->getItem($itemId);
        if (!$item) {
            throw new CartItemNotFoundException();
        }

        $item->setQuantity((int) $data['quantity']);

        $this->entityManager->flush();

        return $this->json($item, 200, [], ['groups' => ['cart:read']]);
    }

    /**
     * @throws CartItemNotFoundException
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}/items/{itemId}', methods: ['DELETE'])]
    public function removeItem(string $id, string $itemId): JsonResponse
    {
        $cart = $this->findCartOrFail($id);

        $item = $cart->getItem($itemId);
        if (!$item) {
            throw new CartItemNotFoundException();
        }

        $cart->removeItem($item);

        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    /**
     * @throws CartNotFoundException
     */
    private function findCartOrFail(string $id): Cart
    {
        $cart = $this->cartRepository->find($id);

        if (!$cart) {
            throw new CartNotFoundException();
        }

        return $cart;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, array<string, string>>
     */
    private function validateItemFields(array $data): array
    {
        $errors = [];

        foreach ($data as $cartItemField => $value) {
            switch ($cartItemField) {
                case 'productId':
                    if (!is_int($value)) {
                        $errors[$cartItemField]['message'] = 'Product ID must be an integer';
                        $errors[$cartItemField]['code'] = 'INVALID_TYPE';
                    }
                    break;
                case 'price':
                    if (!is_numeric($value)) {
                        $errors[$cartItemField]['message'] = 'Price must be a number';
                        $errors[$cartItemField]['code'] = 'INVALID_TYPE';
                    } elseif ($value < 0) {
                        $errors[$cartItemField]['message'] = 'Price cannot be negative';
                        $errors[$cartItemField]['code'] = 'INVALID_VALUE';
                    }
                    break;
                case 'quantity':
                    if (!is_int($value)) {
                        $errors[$cartItemField]['message'] = 'Quantity must be an integer';
                        $errors[$cartItemField]['code'] = 'INVALID_TYPE';
                    } elseif ($value <= 0) {
                        $errors[$cartItemField]['message'] = 'Quantity must be greater than 0';
                        $errors[$cartItemField]['code'] = 'INVALID_VALUE';
                    }
                    break;
            }
        }

        return $errors;
    }
}
