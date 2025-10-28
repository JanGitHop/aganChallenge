<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\AddCartItemDto;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Exception\CartItemNotFoundException;
use App\Exception\CartNotFoundException;
use App\Repository\CartRepository;
use App\Service\CartCacheService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
        private ValidatorInterface $validator,
        private CartCacheService $cacheService,
    ) {
    }

    #[Route('/api/carts', methods: ['GET'])]
    #[OA\Get(
        path: '/api/carts',
        summary: 'List all carts',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'expand',
        description: 'Expand items in response',
        in: 'query',
        schema: new OA\Schema(type: 'string', enum: ['items'])
    )]
    #[OA\Response(
        response: 200,
        description: 'List of carts',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'object')
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $expand = $request->query->get('expand');

        $cachedResponse = $this->cacheService->getCartList(1, 9999, function () use ($expand) {
            $carts = $this->cartRepository->findAll();

            $groups = ['cart:list'];
            if ('items' === $expand) {
                $groups[] = 'cart:read';
            }

            return $this->json($carts, 200, [], ['groups' => $groups]);
        });

        return $cachedResponse;
    }

    #[Route('/api/carts', methods: ['POST'])]
    #[OA\Post(
        path: '/api/carts',
        summary: 'Create a new cart',
        tags: ['Cart']
    )]
    #[OA\Response(
        response: 201,
        description: 'Cart created'
    )]
    public function create(): JsonResponse
    {
        $cart = new Cart();

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        // Invalidate cart list cache
        $this->cacheService->invalidateAllLists();

        return $this->json($cart, 201, [], ['groups' => ['cart:read']]);
    }

    /**
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/carts/{id}',
        summary: 'Get cart by ID',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart details'
    )]
    #[OA\Response(response: 404, description: 'Cart not found')]
    public function show(string $id): JsonResponse
    {
        $cachedResponse = $this->cacheService->getCart($id, function () use ($id) {
            $cart = $this->findCartOrFail($id);
            return $this->json($cart, 200, [], ['groups' => ['cart:read']]);
        });

        return $cachedResponse;
    }

    /**
     * @throws CartNotFoundException
     */
    #[Route('/api/carts/{id}/items', methods: ['POST'])]
    #[OA\Post(
        path: '/api/carts/{id}/items',
        summary: 'Add item to cart',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'productName', 'price', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'integer'),
                    new OA\Property(property: 'productName', type: 'string'),
                    new OA\Property(property: 'price', type: 'number', format: 'float'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                    new OA\Property(property: 'category', type: 'string', nullable: true),
                    new OA\Property(property: 'sku', type: 'string', nullable: true),
                ]
            )
        ),
        tags: ['Cart Items']
    )]
    #[OA\Response(response: 201, description: 'Item added')]
    #[OA\Response(response: 400, description: 'Validation error')]
    #[OA\Response(response: 404, description: 'Cart not found')]
    public function addItem(
        string $id,
        #[MapRequestPayload] AddCartItemDto $dto
    ): JsonResponse {
        $cart = $this->findCartOrFail($id);

        // Note: Validation ensures these are not null, but keep null safety for IDE
        $item = new CartItem(
            $cart,
            $dto->productId ?? 0,
            $dto->productName ?? '',
            $dto->price ?? 0.0,
            $dto->quantity ?? 0,
            $dto->category,
            $dto->sku
        );

        $cart->addItem($item);

        $this->entityManager->flush();

        // Invalidate cache for this cart
        $this->cacheService->invalidateCart($id);

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

        // Create a simple object for validation
        $updateData = new class {
            public int $quantity;
        };

        if (!isset($data['quantity'])) {
            return $this->json([
                'error' => [
                    'message' => 'Quantity required',
                    'code' => 'QUANTITY_REQUIRED',
                ],
            ], 400);
        }

        $updateData->quantity = (int) $data['quantity'];

        // Validate the quantity
        $errors = $this->validator->validate($updateData->quantity, [
            new \Symfony\Component\Validator\Constraints\NotBlank(),
            new \Symfony\Component\Validator\Constraints\Positive(message: 'Quantity must be greater than 0'),
        ]);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json([
                'error' => [
                    'message' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $errorMessages,
                ],
            ], 400);
        }

        $item = $cart->getItem($itemId);
        if (!$item) {
            throw new CartItemNotFoundException();
        }

        $item->setQuantity($updateData->quantity);

        $this->entityManager->flush();

        // Invalidate cache for this cart
        $this->cacheService->invalidateCart($id);

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

        // Invalidate cache for this cart
        $this->cacheService->invalidateCart($id);

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
}
