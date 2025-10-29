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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
  /** @OA\Post(
   *     path="/api/picture",
   *     summary="Ajouter une image",
   *     @OA\RequestBody(
   *         required=true,
   *         description="Données de l'image à ajouter",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="title", type="string", example="Titre de l'image"),
   *             @OA\Property(property="slug", type="string", example="Url de l'image")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Image ajouté avec succès",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="id", type="integer", example=1),
   *             @OA\Property(property="title", type="string", example="Titre de l'image"),
   *             @OA\Property(property="slug", type="string", example="Url de l'image"),
   *             @OA\Property(property="createdAt", type="string", format="date-time")
   *         )
   *     )
   * )
   */
  public function new(Request $request): JsonResponse
  {
    $picture = $this->serializer->deserialize($request->getContent(), Picture::class, 'json');
    $picture->setCreatedAt(new DateTimeImmutable());

    $this->manager->persist($picture);
    $this->manager->flush();

    $responseData = $this->serializer->serialize($picture, 'json');
    $location = $this->urlGenerator->generate(
      'app_api_picture_show',
      ['id' => $picture->getId()],
      UrlGeneratorInterface::ABSOLUTE_URL,
    );

    return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
  }

  /** @OA\Get(
   *     path="/api/picture/{id}",
   *     summary="Afficher une image par ID",
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="ID de l'image à afficher",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Image trouvée avec succès",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="id", type="integer", example=1),
   *             @OA\Property(property="title", type="string", example="Titre de l'image"),
   *             @OA\Property(property="slug", type="string", example="Url de l'image"),
   *             @OA\Property(property="createdAt", type="string", format="date-time")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Image non trouvée"
   *     )
   * )
   */
  #[Route('/{id}', name: 'show', methods: 'GET')]
  public function show(int $id): JsonResponse
  {
    $picture = $this->repository->findOneBy(['id' => $id]);
    if ($picture) {
      $responseData = $this->serializer->serialize($picture, 'json');

      return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }

  /** @OA\Put(
   *     path="/api/picture/{id}",
   *     summary="Modifier une image par ID",
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="ID de l'image à modifier",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         description="Nouvelles données du restaurant à mettre à jour",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="title", type="string", example="Nouveau nom de l'image"),
   *             @OA\Property(property="slug", type="string", example="Nouvelle url de l'image")
   *         )
   *     ),
   *     @OA\Response(
   *         response=204,
   *         description="Image modifiée avec succès"
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Image non trouvée"
   *     )
   * )
   */
  #[Route('/{id}', name: 'edit', methods: 'PUT')]
  public function edit(int $id, Request $request): JsonResponse
  {
    $picture = $this->repository->findOneBy(['id' => $id]);
    if ($picture) {
      $picutre = $this->serializer->deserialize(
        $request->getContent(),
        Picture::class,
        'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $picture]
      );
      $picture->setUpdatedAt(new DateTimeImmutable());

      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }

  /** @OA\Delete(
   *     path="/api/picture/{id}",
   *     summary="Supprimer une image par ID",
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="ID de l'image à supprimer",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=204,
   *         description="Image supprimée avec succès"
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Image non trouvé"
   *     )
   * )
   */
  #[Route('/{id}', name: 'delete', methods: 'DELETE')]
  public function delete(int $id): JsonResponse
  {
    $picture = $this->repository->findOneBy(['id' => $id]);
    if ($picture) {
      $this->manager->remove($picture);
      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
  }
}
