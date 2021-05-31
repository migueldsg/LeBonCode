<?php

namespace App\Controller;

use App\Entity\Advert;
use App\Form\AdvertType;
use App\Repository\AdvertRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/advert")
 */
class AdvertController extends AbstractController
{
    /**
     * @Route("/", name="advert_index", methods={"GET"})
     */
    public function index(AdvertRepository $advertRepository, NormalizerInterface $normalizer): Response
    {
        $adverts = $advertRepository->findAll();
        return $this->json($adverts, 200, [], [
            'groups' => 'advert:read'
        ]);
    }

    /**
     * @Route("/", name="advert_new", methods={"POST"})
     */
    public function new(EntityManagerInterface $entityManager, Request $request,
    SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        $content = $request->getContent();
        try {
            $advert = $serializer->deserialize($content, Advert::class, 'json');
            $errors = $validator->validate($advert);
            if (count($errors) > 0) {
                return $this->json($errors, 400);
            }
            $entityManager->persist($advert);
            $entityManager->flush();
            return $this->json($advert, 201, [], [
                'groups' => 'advert:read'
            ]);        
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }    
    }

    /**
     * @Route("/{id}/edit", name="advert_edit", methods={"PATCH"})
     */
    public function edit(EntityManagerInterface $entityManager, Request $request, SerializerInterface $serializer): Response
    {
        $content = $request->getContent();
        try {
            $advert = $serializer->deserialize($content, Advert::class, 'json');
            $dbAdvert = $entityManager->getRepository(Advert::class)->find($request->get('id'));

            $dbAdvert->setTitle($advert->getTitle());
            $dbAdvert->setDescription($advert->getDescription());
            $dbAdvert->setPrice($advert->getPrice());
            $dbAdvert->setCity($advert->getCity());
            
            $entityManager->flush();    
            return $this->json([
                'message' => 'Advert modifié avec succès.',
                'advert' => $dbAdvert
            ]);    
        } catch (NotEncodableValueException $e){
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);                
        }
    }

    /**
     * @Route("/search", name="advert_search", methods={"GET"})
     */
    public function search(AdvertRepository $advertRepository, EntityManagerInterface $entityManager, Request $request, SerializerInterface $serializer): Response
    {
        $content = json_decode($request->getContent(), true);
        $adverts = $advertRepository->findAll();
        $searchList = [];
        try {
            foreach ($adverts as $advert) {
                if ($advert->getPrice() > $content['price_min'] && $advert->getPrice() < $content['price_max'] && str_contains($advert->getTitle(), $content['title'])) {
                    array_push($searchList, $advert);
                }
            }
            return $this->json([
                'adverts' => $searchList
            ]);    
        } catch (NotEncodableValueException $e){
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);                
        }
    }

    /**
     * @Route("/{id}", name="advert_show", methods={"GET"})
     */
    public function show(Advert $advert)
    {
        if ($advert) {
            return $this->json($advert, 200, [], [
                'groups' => 'advert:read'    
            ]);
        } else {
            return $this->json([
                'message' => 'Advert inexistant.'
            ]);    
        }
    }

    /**
     * @Route("/{id}", name="advert_delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $entityManager, Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $advert = $entityManager->getRepository(Advert::class)->find($request->get('id'));
        if ($advert) {
            try {
                $entityManager->remove($advert);
                $entityManager->flush();    
                return $this->json([
                    'message' => 'Advert supprimé avec succès.'
                ]);    
            } catch (NotEncodableValueException $e){
                return $this->json([
                    'status' => 400,
                    'message' => $e->getMessage()
                ]);                
            }
        }
    }
}
