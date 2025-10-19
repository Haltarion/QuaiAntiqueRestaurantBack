<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Restaurant;
use App\Repository\PictureRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response, File\UploadedFile};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/picture', name: 'app_api_picture_')]
class PictureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PictureRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $title = $request->request->get('title');
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$title || !$file) {
            return new JsonResponse(['error' => 'Tous les champs sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->manager->getRepository(Restaurant::class)->find(1);
        if (!$restaurant) {
            return new JsonResponse(['error' => 'Restaurant introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $filename = uniqid() . '-' . $file->getClientOriginalName();
        $file->move($uploadsDir, $filename);

        $picture = new Picture();
        $picture->setTitle($title);
        $picture->setSlug('uploads/' . $filename);
        $picture->setRestaurant($restaurant);
        $picture->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($picture);
        $this->manager->flush();

        $location = $this->urlGenerator->generate(
            'app_api_picture_show',
            ['id' => $picture->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'restaurant' => 1,
                'createdAt' => $picture->getCreatedAt()->format('c')
            ],
            Response::HTTP_CREATED,
            ["Location" => $location]
        );
    }

    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $picture = $this->repository->find($id);
        if (!$picture) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'restaurant' => 1,
                'createdAt' => $picture->getCreatedAt()->format('c')
            ]
        );
    }

    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $picture = $this->repository->find($id);
        if (!$picture) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $title = $request->request->get('title');
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if ($title) {
            $picture->setTitle($title);
        }

        if ($file) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            $filename = uniqid() . '-' . $file->getClientOriginalName();
            $file->move($uploadsDir, $filename);
            $picture->setSlug('uploads/' . $filename);
        }

        $picture->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $picture = $this->repository->find($id);
        if (!$picture) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->manager->remove($picture);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'list', methods: 'GET')]
    public function list(): JsonResponse
    {
        $pictures = $this->repository->findAll();
        $data = [];

        foreach ($pictures as $picture) {
            $data[] = [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'restaurant' => 1,
                'createdAt' => $picture->getCreatedAt()->format('c')
            ];
        }

        return new JsonResponse($data);
    }
}
