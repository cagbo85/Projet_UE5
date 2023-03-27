<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Personne;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RegistrationFormType;


use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AdminController extends AbstractController
{

    #[Route('/administration', name: 'pageAdmin')]
    public function index(Request $request, UserRepository $repo): Response
    {   
        $listePersonnes = $repo->findAll();

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/accueilAdmin.html.twig', ['listePersonnes' => $listePersonnes]);
    }

    #[Route('/supprimer-{id}', name: 'deletePersonne')]
    public function delete(Request $request, UserRepository $repo, int $id, EntityManagerInterface $entityManager): Response
    {
        $listePersonnes = $repo->find($id);

        if (!$listePersonnes) {
            throw $this->createNotFoundException(
                'Aucune personne ne correspond à cette id ' . $id
            );
        }

        $entityManager->remove($listePersonnes);
        $entityManager->flush();
        $this->addFlash('success', "La personne a bien été supprimée !");

        return $this->redirectToRoute('pageAdmin');
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/listePersonnesSuppr.html.twig', [
            'listePersonnes' => $listePersonnes
        ]);
    }

    #[Route('/modifie-{id}', name: 'laPersonne_Modif')]
    public function updatePersonne(Request $request, EntityManagerInterface $entityManager, int $id, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $idConnecter = $this->getUser()->getId();

        $personne = $entityManager->getRepository(Personne::class)->find($id);

        if (!$this->isGranted('ROLE_ADMIN') && $idConnecter !== $personne->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier cette personne.');
        }


        
        $monForm = $this->createForm(RegistrationFormType::class, $personne);
        $monForm->setData($personne);
        $monForm->handleRequest($request);

        if (!$personne) {
            throw $this->createNotFoundException('La personne avec l\'identifiant '.$id.' n\'existe pas.');
        }

        if ($monForm->isSubmitted() && $monForm->isValid()) {
            

            $personneExistante = $entityManager->getRepository(Personne::class)->findOneBy([
                'nom' => $personne->getNom(),
                'prenom' => $personne->getPrenom(),
                'date_naissance' => $personne->getDateNaissance(),
            ]);

            if ($personneExistante && $personneExistante->getId() !== $id) {
                $this->addFlash('error', 'Une personne avec le même nom, prénom et date de naissance existe déjà.');
                // return $this->redirectToRoute('formulaire_ajout_pers');
            }
            $personne->setMotDePasse(
                $userPasswordHasher->hashPassword(
                    $personne,
                    $monForm->get('mot_de_passe')->getData()
                )
            );

            $entityManager->persist($personne);
            $entityManager->flush();

            $this->addFlash('success', 'La personne a été modifiée avec succès.');

            return $this->redirectToRoute('home');
        }
        return $this->render('admin/personneModif.html.twig', ['monForm' => $monForm->createView(), 'personne' => $personne]);
    }
}
