<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\BadCredentialsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository, NormalizerInterface $normalizer): Response
    {
        return $this->json([
            'status' => $userRepository->findAll()
        ]);

        $users = $userRepository->findAll();
        return $this->json($users, 200, [], [
            'groups' => 'user:read'
        ]);
    }

    /**
     * @Route(name="login", path="/login_check", methods={"POST"})
     * @return JsonResponse
     */
    public function login(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        // $content = $request->getContent();
        // try {
        //     $user = $serializer->deserialize($content, User::class, 'json');
        //     // $user = $entityManager->getRepository(User::class)->find($request->get('email'));

        //     // if (!$user) {
        //     //     throw $this->createNotFoundException();
        //     // }

        //     // $isValid = $this->get('security.password_encoder')
        //     //     ->isPasswordValid($user, $request->getPassword());
        //     // return $this->json([
        //     //     'message' => 'message'
        //     // ]);               
        // } catch (NotEncodableValueException $e){
        //     return $this->json([
        //         'status' => 400,
        //         'message' => $e->getMessage()
        //     ]);                
        // }
    }

    /**
     * @Route("/register", name="user_register", methods={"POST"})
     */
    public function new(EntityManagerInterface $entityManager, Request $request,
    SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        $content = $request->getContent();
        try {
            $user = $serializer->deserialize($content, User::class, 'json');
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                return $this->json($errors, 400);
            }
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->json($user, 201, [], [
                'message' => 'Utilisateur créé avec succès.'
            ]);        
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }    
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}
