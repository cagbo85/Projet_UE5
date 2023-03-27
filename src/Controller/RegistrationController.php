<?php

namespace App\Controller;

use App\Entity\Personne;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $unePersonne = new Personne();
        $form = $this->createForm(RegistrationFormType::class, $unePersonne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $personneExistante = $entityManager->getRepository(Personne::class)->findOneBy([
                'nom' => $unePersonne->getNom(),
                'prenom' => $unePersonne->getPrenom(),
                'date_naissance' => $unePersonne->getDateNaissance(),
            ]);
            if ($personneExistante) {
                $this->addFlash('error', 'Une personne avec le même nom, prénom et date de naissance existe déjà.');
                return $this->redirectToRoute('app_register');
            }

            // encode the plain password
            $unePersonne->setMotDePasse(
                $userPasswordHasher->hashPassword(
                    $unePersonne,
                    $form->get('mot_de_passe')->getData()
                )
            );

            $entityManager->persist($unePersonne);
            $entityManager->flush();
            $this->addFlash('success', "Vous êtes bien inscrit, connectez vous maintenant.");
            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
